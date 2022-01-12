<?php

namespace Ibelousov\AdvancedNestedSet;

class TreeCollection extends \Illuminate\Database\Eloquent\Collection
{
    protected $isTree = false;

    public function __construct($items = [], $isTree = false)
    {
        $this->isTree = $isTree;

        parent::__construct($items);
    }

    public function toTree($root = null)
    {
        $tree = new self([], true);

        foreach ($this as $item) {
            if ($item->parent_id == $root) {
                $item->setAttribute('children', $this->toTree($item->id));
                $tree->push($item);
            }
        }

        return $tree;
    }

    public function toArray()
    {
        if (! $this->isTree) {
            return $this->toArray();
        }

        return $this->toTreeArray();
    }

    protected function toTreeArray()
    {
        $array = [];

        foreach ($this as $item) {
            $item = $item->toArray();
            $item['children'] = $item['children']->toArray();
            $array[] = $item;
        }

        return $array;
    }
}
