<?php

namespace Ibelousov\AdvancedNestedSet\Utilities;

use Illuminate\Support\Facades\DB;

class CorrectnessChecker
{
    protected $elements;
    protected $elementsGrouped;
    protected $errorsCount;

    protected static $instance;

    public static function errors($table)
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance->check($table);
    }

    public static function isCorrect($table)
    {
        return 0 == self::errorsCount($table);
    }

    public static function errorsCount($table)
    {
        return count(self::errors($table));
    }

    protected function check($table)
    {
        $this->elements = $this->getQuery($table)->keyBy('id');
        $this->elementsGrouped = $this->getQuery($table)->groupBy('parent_id');

        $tree = $this->buildCorrectTree();

        return $this->checkTree($tree);
    }

    protected function getQuery($table)
    {
        return DB::table($table)->orderBy('lft')->get();
    }

    protected function buildCorrectTree($elementId = null, $depth = 0)
    {
        $children = collect([]);

        if (isset($this->elementsGrouped[$elementId])) {
            $children = $this->elementsGrouped[$elementId]->map(function ($element) use ($depth) {
                return $this->buildCorrectTree($element->id, $depth + 1);
            });
        }

        return [
            'id' => $elementId,
            'children' => $children->toArray(),
            'children_count' => $children->count() + $children->sum('children_count'),
            'depth' => $depth,
        ];
    }

    protected function checkTree($tree, $lft = -1)
    {
        static $errors = [];

        if ($tree['id']) {
            $element = $this->elements[$tree['id']];

            $lftCorrect = $element->lft == ($lft + 1);
            $rgtCorrect = $element->rgt == ($lft + ($tree['children_count'] ?? 0) * 2 + 2);
            $depthCorrect = $element->depth == $tree['depth'];

            if (! $lftCorrect || ! $rgtCorrect || ! $depthCorrect) {
                $errors[] = sprintf(
                    'У элемента с id = %7s значения - lft: %7s, rgt: %7s, depth: %2s, а должны быть lft: %7s, rgt: %7s, depth:%2s',
                    $element->id, $element->lft ?? 'NULL', $element->rgt ?? 'NULL', $element->depth ?? 'NULL', $lft + 1, $lft + ($tree['children_count'] ?? 0) * 2 + 2, $tree['depth']
                );
                $this->errorsCount++;
            }
        }

        foreach ($tree['children'] as $children) {
            $this->checkTree($children, $lft + 1);
            $lft += ($children['children_count'] * 2) + 2;
        }

        return $errors;
    }
}
