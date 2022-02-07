<?php

namespace Ibelousov\AdvancedNestedSet\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class ParentsAndSelf extends Relation
{
    protected $query;
    protected $parent;

    public function __construct($parent)
    {
        parent::__construct($parent::query()->withoutGlobalScopes(), $parent);
    }

    protected function parentWhereClause($query, $table, $subTable, $lft, $depth)
    {
        $subTable = str_replace('.','_', $subTable);
        
        return $query->join(
            DB::raw("(SELECT MAX(lft) as max_lft FROM $table WHERE lft <= $lft AND depth <= $depth GROUP BY depth) AS $subTable"),
            "$subTable.max_lft", '=', "$table.lft"
        );
    }

    public function addConstraints()
    {
        if ($this->parent->lft && $this->parent->rgt && $this->parent->depth) {
            $this->parentWhereClause(
                $this->query, $this->parent->getModel()->getTable(), $this->parent->getModel()->getTable().time(), $this->parent->lft, $this->parent->depth
            );
        }
    }

    public function addEagerConstraints(array $items)
    {
        $this->query->where(function ($query) use ($items) {
            foreach ($items as $key => $item) {
                $query->{$key == 0 ? 'where' : 'orWhere'}(function ($query) use ($item) {
                    $this->parentWhereClause(
                        $query, $item->getTable(), $item->getTable().time(), (int) $item->lft, (int) $item->depth,
                    );
                });
            }
        });
    }

    public function initRelation(array $items, $relation)
    {
        $class = (get_class($this->parent));

        foreach ($items as $item) {
            $item->setRelation($relation, new $class);
        }

        return $items;
    }

    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $tableName = $this->parent->getTable();

        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        return $query->select($columns)->whereColumn(
            "$tableName.lft", '>=', "$hash.lft"
        )->whereColumn(
            "$tableName.rgt", '<=', "$hash.rgt"
        );
    }

    public function match(array $items, Collection $parents, $relation)
    {
        if ($parents->isEmpty()) {
            return $items;
        }

        foreach ($items as $item) {
            $item->setRelation(
                $relation,
                $parents->filter(function ($parent) use ($item) {
                    return $item->rgt && $item->lft && (($parent->lft <= $item->lft) && ($parent->rgt >= $item->rgt));
                })->sortBy('lft')
            );
        }

        return $items;
    }

    public function getResults()
    {
        return $this->query->get();
    }
}
