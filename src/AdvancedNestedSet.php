<?php

namespace Ibelousov\AdvancedNestedSet;

use Ibelousov\AdvancedNestedSet\Exceptions\UnsupportedDatabaseException;
use Ibelousov\AdvancedNestedSet\Relations\Descendants;
use Ibelousov\AdvancedNestedSet\Relations\DescendantsAndSelf;
use Ibelousov\AdvancedNestedSet\Relations\Parents;
use Ibelousov\AdvancedNestedSet\Relations\ParentsAndSelf;
use Ibelousov\AdvancedNestedSet\Utilities\CorrectnessChecker;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait AdvancedNestedSet
{
    use SoftDeletes;

    // lock prefix
    public static $ADVANCED_NESTED_SET_LOCK_NAME = 'ADVANCED_NESTED_SET_LOCK_';

    // seconds to wait before throw exception
    public static $ADVANCED_NESTED_LOCK_WAIT = 30;

    // microseconds to delay after lock callback executed
    public static $ADVANCED_NESTED_LOCK_DELAY = 100000;

    // supported databases
    public static $SUPPORTED_DRIVERS = ['sqlite', 'pgsql', 'mysql'];

    public static function bootAdvancedNestedSet()
    {
        if (! in_array((new static)->getConnection()->getName(), self::$SUPPORTED_DRIVERS)) {
            throw new UnsupportedDatabaseException((new static)->getConnection()->getName());
        }

        static::created(function ($item) {
            self::lock(function () use ($item) {
                DB::transaction(function () use ($item) {
                    $parent = static::withoutGlobalScopes()->firstWhere('id', $item->parent_id);
                    $item->distance = 1;

                    if (! $parent) {
                        $item->lft = static::withoutGlobalScopes()->max('rgt') + 1;
                        $item->rgt = static::withoutGlobalScopes()->max('rgt') + 2;
                        $item->depth = 1;
                        $item->parent_id = null;
                    } else {
                        $parent->parents()->withoutGlobalScopes()->update(['rgt' => DB::raw('rgt + 2'), 'distance' => DB::raw('distance + 2')]);
                        $parent->rgt = DB::raw('rgt + 2');
                        $parent->distance = DB::raw('distance + 2');
                        $parent->saveQuietly();

                        static::withoutGlobalScopes()->where('lft', '>', $parent->lft)->update(['lft' => DB::raw('lft + 2'), 'rgt' => DB::raw('rgt+2')]);

                        $item->depth = $parent->depth + 1;
                        $item->lft = $parent->lft + 1;
                        $item->rgt = $parent->lft + 2;
                    }

                    $item->saveQuietly();
                });
            });
        });

        static::updated(function ($item) {
            self::lock(function () use ($item) {
                if (($item->parent_id != $item->getOriginal('parent_id')) && ! $item->descendants_and_self()->withoutGlobalScopes()->firstWhere('id', $item->parent_id)) {
                    DB::transaction(function () use ($item) {
                        $childrenAndSelf = $item->descendants_and_self;

                        if ($childrenAndSelf->firstWhere('id', $item->parent_id)) {
                            return;
                        }

                        $ids = $childrenAndSelf->pluck('id')->toArray();
                        $newParent = optional(self::withoutGlobalScopes()->where('id', $item->parent_id)->first());
                        $newParentParentsIds = $newParent->parents ? $newParent->parents->pluck('id')->toArray() : [];
                        $shift = ($item->rgt - $item->lft) + 1;
                        $lftMinus = DB::raw(sprintf('lft - %s', $shift));
                        $rgtMinus = DB::raw(sprintf('rgt - %s', $shift));
                        $lftPlus = DB::raw(sprintf('lft + %s', $shift));
                        $rgtPlus = DB::raw(sprintf('rgt + %s', $shift));
                        $distanceMinus = DB::raw(sprintf('distance - %s', $item->rgt - $item->lft));
                        $distancePlus = DB::raw(sprintf('distance + %s', $item->rgt - $item->lft));

                        $depthShift = DB::raw(
                            sprintf(
                                'depth %s %s',
                                $newParent->depth + 1 >= $item->depth ? '+' : '-',
                                $newParent->depth + 1 == $item->depth ? '0' : abs(($newParent->depth ?: 0) - $item->depth + 1))
                        );

                        $item->parents()->withoutGlobalScopes()->update(['rgt' => $rgtMinus, 'distance' => $distanceMinus]);
                        static::withoutGlobalScopes()
                            ->where('lft', '>', $item->rgt)
                            ->update(['lft' => $lftMinus, 'rgt' => $rgtMinus]);
                        static::withoutGlobalScopes()
                            ->where('lft', '>', (int) optional(static::find($newParent->id))->lft)
                            ->whereNotIn('id', $ids)
                            ->update(['lft' => $lftPlus, 'rgt' => $rgtPlus]);
                        static::withoutGlobalScopes()
                            ->whereIn('id', array_filter(array_merge($newParentParentsIds, [$newParent->id])))
                            ->update(['rgt' => $rgtPlus, 'distance' => $distancePlus]);

                        $newParentLft = optional(self::find((int) optional($newParent)->id))->lft;
                        $sign = $item->lft > $newParentLft ? '-' : '+';
                        $difference = abs(optional(static::find((int) $newParent->id))->lft + 1 - $item->lft);
                        $lftMove = DB::raw(sprintf('lft %s %s', $sign, $difference));
                        $rgtMove = DB::raw(sprintf('rgt %s %s', $sign, $difference));
                        static::withoutGlobalScopes()->whereIn('id', $ids)->update(['lft' => $lftMove, 'rgt' => $rgtMove, 'depth' => $depthShift]);
                    });
                } elseif ($item->isDirty('parent_id')) {
                    $item->parent_id = $item->getRawOriginal('parent_id');
                }
            });
        });

        static::deleted(function ($item) {
            self::lock(function () use ($item) {
                $item->fresh()->descendants()->withoutGlobalScopes()->delete();
            });
        });
    }

    public function moveAfter(self $afterEl)
    {
        self::lock(function () use ($afterEl) {
            if ($afterEl->parent_id != $this->parent_id || $this->id == $afterEl->id) {
                return;
            }

            $shift = (string) ((int) ($this->lft < $afterEl->lft ? abs($this->lft - $afterEl->rgt) - ($this->rgt - $this->lft) : abs($afterEl->rgt - $this->lft) - 1));
            $shiftSign = ($this->lft > $afterEl->lft ? '-' : '+');
            $shiftAfter = $this->lft > $afterEl->lft ? 0 : (string) ((int) ($this->rgt - $this->lft + 1));
            $shiftOther = (string) ((int) ($this->rgt - $this->lft + 1));
            $shiftAfterSign = ($afterEl->lft > $this->lft ? '-' : '+');

            self::withoutGlobalScopes()
                ->where('lft', '>=', min($this->lft, $afterEl->lft))
                ->where('rgt', '<=', max($this->rgt, $afterEl->rgt))
                ->update(
                    [
                        'lft' => DB::raw("CASE 
                            WHEN lft >= {$this->lft} AND rgt <= {$this->rgt}
                            THEN lft{$shiftSign}{$shift}
                            WHEN lft >= {$afterEl->lft} AND rgt <= {$afterEl->rgt}
                            THEN lft{$shiftAfterSign}{$shiftAfter}
                            ELSE lft{$shiftAfterSign}{$shiftOther}
                        END"),
                    ]
                );

            self::withoutGlobalScopes()->update(['rgt' => DB::raw('lft + distance')]);
        });
    }

    public static function print()
    {
        printf("\n----------------------------------------------------------------------\n");
        printf("%-25s %10s %10s %10s %10s %10s\n", 'EL', 'LEFT', 'RIGHT', 'PAREN', 'DEPTH', 'DISTANCE');
        self::all()->sortBy('lft')->each(
            fn ($el) => printf("%-25s %10s %10s %10s %10s %10s\n",
                sprintf('%s ID:%2s %10s', str_repeat('-', $el->depth), $el->id, $el->name), $el->lft, $el->rgt, $el->parent_id, $el->depth, $el->distance)
        );
        printf("\n----------------------------------------------------------------------\n");
    }

    public static function lock($call)
    {
        $lockName = env('ADVANCED_NESTED_LOCK_NAME', AdvancedNestedSet::$ADVANCED_NESTED_SET_LOCK_NAME).static::class;
        $blockWait = env('ADVANCED_NESTED_LOCK_WAIT', AdvancedNestedSet::$ADVANCED_NESTED_LOCK_WAIT);
        $blockDelay = env('ADVANCED_NESTED_LOCK_DELAY', self::$ADVANCED_NESTED_LOCK_DELAY);

        if ($blockWait) {
            Cache::lock($lockName)->block($blockWait, function () use ($call) {
                $call();
            });
        } else {
            $call();
        }

        if ($blockDelay) {
            usleep($blockDelay);
        }
    }

    public function newCollection(array $models = [])
    {
        return new TreeCollection($models);
    }

    /**
     * return true if nested set is correct.
     *
     * @return bool
     */
    public static function isCorrect()
    {
        return CorrectnessChecker::isCorrect((new static)->getTable());
    }

    /**
     * return errors if nested set is not correct.
     *
     * @return array
     */
    public static function errors()
    {
        return CorrectnessChecker::errors((new static)->getTable());
    }

    /**
     * @param $table
     * @return int
     */
    public static function errorsCount()
    {
        return CorrectnessChecker::errorsCount((new static)->getTable());
    }

    /**
     * @return Descendants
     */
    public function descendants()
    {
        return (new Descendants($this))->orderBy('lft');
    }

    /**
     * @return DescendantsAndSelf
     */
    public function descendants_and_self()
    {
        return (new DescendantsAndSelf($this))->orderBy('lft');
    }

    /**
     * @return Parents
     */
    public function parents()
    {
        return (new Parents($this))->orderBy('lft');
    }

    /**
     * @return ParentsAndSelf
     */
    public function parents_and_self()
    {
        return (new ParentsAndSelf($this))->orderBy('lft');
    }

    /**
     * @return mixed
     */
    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * @return mixed
     */
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id', 'id');
    }
}
