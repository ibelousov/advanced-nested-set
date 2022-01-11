<?php

namespace Ibelousov\AdvancedNestedSet;

use Closure;
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
    public static $SUPPORTED_DRIVERS = ['sqlite','pgsql'];

    public static function bootAdvancedNestedSet()
    {
        if(!in_array((new static)->getConnection()->getName(), self::$SUPPORTED_DRIVERS))
            throw new UnsupportedDatabaseException((new static)->getConnection()->getName());

        static::created(function($item) {
            self::lock(function() use ($item) {
                DB::transaction(function () use ($item) {
                    $parent = static::withoutGlobalScopes()->firstWhere('id', $item->parent_id);

                    if (!$parent) {

                        $item->lft = static::withoutGlobalScopes()->max('rgt') + 1;
                        $item->rgt = static::withoutGlobalScopes()->max('rgt') + 2;
                        $item->depth = 1;
                        $item->parent_id = null;

                    } else {

                        $parent->parents()->withoutGlobalScopes()->update(['rgt' => DB::raw('rgt + 2')]);
                        $parent->rgt = DB::raw('rgt + 2');
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
            self::lock(function() use ($item) {
                if (($item->parent_id != $item->getOriginal('parent_id')) && !$item->descendants_and_self()->withoutGlobalScopes()->firstWhere('id', $item->parent_id)) {
                    DB::transaction(function () use ($item) {
                        $childrenAndSelf = $item->descendants_and_self;

                        if ($childrenAndSelf->firstWhere('id', $item->parent_id))
                            return;

                        $ids = $childrenAndSelf->pluck('id')->toArray();
                        $newParent = optional(self::withoutGlobalScopes()->where('id', $item->parent_id)->first());
                        $newParentParentsIds = $newParent->parents ? $newParent->parents->pluck('id')->toArray() : [];
                        $shift = ($item->rgt - $item->lft) + 1;
                        $lftMinus = DB::raw(sprintf('lft - %s', $shift));
                        $rgtMinus = DB::raw(sprintf('rgt - %s', $shift));
                        $lftPlus = DB::raw(sprintf('lft + %s', $shift));
                        $rgtPlus = DB::raw(sprintf('rgt + %s', $shift));

                        $depthShift = DB::raw(
                            sprintf(
                                'depth %s %s',
                                $newParent->depth + 1 >= $item->depth ? '+' : '-',
                                $newParent->depth + 1 == $item->depth ? '0' : abs(($newParent->depth ?: 0) - $item->depth + 1))
                        );

                        $item->parents()->withoutGlobalScopes()->update(['rgt' => $rgtMinus]);
                        static::withoutGlobalScopes()->where('lft', '>', $item->rgt)->update(['lft' => $lftMinus, 'rgt' => $rgtMinus]);
                        static::withoutGlobalScopes()->where('lft', '>', (int)optional(static::find($newParent->id))->lft)->whereNotIn('id', $ids)->update(['lft' => $lftPlus, 'rgt' => $rgtPlus]);
                        static::withoutGlobalScopes()->whereIn('id', array_filter(array_merge($newParentParentsIds, [$newParent->id])))->update(['rgt' => $rgtPlus]);

                        $newParentLft = optional(self::find((int)optional($newParent)->id))->lft;
                        $sign = $item->lft > $newParentLft ? '-' : '+';
                        $difference = abs(optional(static::find((int)$newParent->id))->lft + 1 - $item->lft);
                        $lftMove = DB::raw(sprintf('lft %s %s', $sign, $difference));
                        $rgtMove = DB::raw(sprintf('rgt %s %s', $sign, $difference));
                        static::withoutGlobalScopes()->whereIn('id', $ids)->update(['lft' => $lftMove, 'rgt' => $rgtMove, 'depth' => $depthShift]);
                    });

                } else if ($item->isDirty('parent_id')) {
                    $item->parent_id = $item->getRawOriginal('parent_id');
                }
            });
        });

        static::deleted(function($item) {
            self::lock(function() use ($item) {
                $item->fresh()->descendants()->withoutGlobalScopes()->delete();
            });
        });
    }

    public function moveAfter(self $afterEl)
    {
        self::lock(function() use($afterEl) {
            if ($afterEl->parent_id != $this->parent_id || $this->id == $afterEl->id)
                return;

            $shift = (string)((int)($this->lft < $afterEl->lft ? abs($this->lft - $afterEl->rgt) - ($this->rgt - $this->lft) : abs($afterEl->rgt - $this->lft) - 1));
            $shiftAfter = (string)((int)($this->rgt - $this->lft + 1));

            $sql = sprintf('UPDATE %s SET (lft,rgt) = (
                    SELECT
                        CASE WHEN lft >= %s AND rgt <= %s THEN lft%s%s WHEN lft >= %s AND rgt <= %s THEN lft%s%s ELSE lft%s%s END,
                        CASE WHEN lft >= %s AND rgt <= %s THEN rgt%s%s WHEN lft >= %s AND rgt <= %s THEN rgt%s%s ELSE rgt%s%s END
                    FROM
                    %s as t
                    WHERE t.id=%s.id
                ) WHERE lft >= %s AND rgt <= %s',
                $this->getTable(),
                $this->lft, $this->rgt, ($this->lft > $afterEl->lft ? '-' : '+'), $shift,
                $afterEl->lft, $afterEl->rgt, ($afterEl->lft > $this->lft ? '-' : '+'), $this->lft > $afterEl->lft ? 0 : $shiftAfter,
                ($afterEl->lft > $this->lft ? '-' : '+'), $afterEl->lft > $this->lft ? $shiftAfter : $shiftAfter,
                $this->lft, $this->rgt, ($this->lft > $afterEl->lft ? '-' : '+'), $shift,
                $afterEl->lft, $afterEl->rgt, ($afterEl->lft > $this->lft ? '-' : '+'), $this->lft > $afterEl->lft ? 0 : $shiftAfter,
                ($afterEl->lft > $this->lft ? '-' : '+'), $afterEl->lft > $this->lft ? $shiftAfter : $shiftAfter,
                $this->getTable(),
                $this->getTable(),
                min($this->lft, $afterEl->lft),
                max($this->rgt, $afterEl->rgt)
            );

            DB::update($sql);
        });
    }

    public static function print()
    {
        printf("\n----------------------------------------------------------------------\n");
        printf("%-25s %10s %10s %10s %10s\n", 'EL','LEFT','RIGHT','PAREN','DEPTH');
        self::all()->sortBy('lft')->each(
            fn($el) => printf("%-25s %10s %10s %10s %10s\n", sprintf("%s ID:%2s %10s", str_repeat("-", $el->depth), $el->id,$el->name),$el->lft,$el->rgt,$el->parent_id,$el->depth)
        );
        printf("----------------------------------------------------------------------\n");
    }

    public static function lock($call)
    {
        $lockName = env('ADVANCED_NESTED_LOCK_NAME', AdvancedNestedSet::$ADVANCED_NESTED_SET_LOCK_NAME) . static::class;
        $blockWait = env('ADVANCED_NESTED_LOCK_WAIT', AdvancedNestedSet::$ADVANCED_NESTED_LOCK_WAIT);
        $blockDelay = env('ADVANCED_NESTED_LOCK_DELAY', self::$ADVANCED_NESTED_LOCK_DELAY);

        if($blockWait)
            Cache::lock($lockName)->block($blockWait, function() use($call) {
                $call();
            });
        else
            $call();

        if($blockDelay)
            usleep($blockDelay);
    }

    public function newCollection(array $models = [])
    {
        return new TreeCollection($models);
    }

    /**
     * return true if nested set is correct
     * @return bool
     */
    public static function isCorrect()
    {
        return CorrectnessChecker::isCorrect((new static)->getTable());
    }

    /**
     * return errors if nested set is not correct
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
