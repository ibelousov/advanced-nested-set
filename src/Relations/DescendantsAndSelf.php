<?php

namespace Ibelousov\AdvancedNestedSet\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class DescendantsAndSelf extends Relation
{
    protected $query;
    protected $parent;

    public function __construct($parent)
    {
        parent::__construct($parent::query()->orderBy('lft'), $parent);
    }

    public function addConstraints()
    {
        if($this->parent->lft && $this->parent->rgt)
            $this->query->where(function($query) {
                $query->where('lft', '>=', (int)$this->parent->lft)->where('rgt', '<=', (int)$this->parent->rgt);
            });
    }

    public function addEagerConstraints(array $items)
    {
        $this->query->where(function($query) use($items) {
            foreach($items as $key => $item) {
                $query->{$key == 0 ? 'where' : 'orWhere'}(function ($query) use ($item) {
                    $query->where('lft', '>=', (int)$item->lft)->where('rgt', '<=', (int)$item->rgt);
                });
            }
        });
    }

    public function initRelation(array $items, $relation)
    {
        $class = (get_class($this->parent));

        foreach ($items as $item)
            $item->setRelation($relation, new $class);

        return $items;
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $tableName = $this->parent->getTable();

        $query->from($query->getModel()->getTable() . ' as ' . $hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        return $query->select($columns)->whereColumn(
            "$tableName.lft", '<=', "$hash.lft"
        )->whereColumn(
            "$tableName.rgt", '>=', "$hash.rgt"
        );
    }

    public function getRelationCountHash($incrementJoinCount = true)
    {
        return 'laravel_reserved_'.($incrementJoinCount ? static::$selfJoinCount++ : static::$selfJoinCount);
    }

    public function match(array $items, Collection $children, $relation)
    {
        if ($children->isEmpty()) {
            return $items;
        }

        foreach ($items as $item) {
            $item->setRelation(
                $relation,
                $children->filter(function ($child) use ($item) {
                    return $item->rgt && $item->lft && (($child->lft >= $item->lft) && ($child->rgt <= $item->rgt));
                })
            );
        }

        return $items;
    }

    public function getResults()
    {
        return $this->query->get();
    }
}
