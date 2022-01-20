<?php

namespace Ibelousov\AdvancedNestedSet\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Fix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advanced-nested-set:fix {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild Nested Set for any chosen table';

    protected $records;
    protected $tree;
    protected $sql = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Get entries from database...');
        $this->records = collect(
            DB::table($this->argument('table'))->select('id', 'lft', 'rgt', 'parent_id', 'depth')->orderBy('lft')->get()
        )->groupBy('parent_id');

        $this->info('Building tree...');
        $this->tree = $this->buildNestedSet($this->records);

        $this->info('Make queries for update...');
        $this->buildQueriesFromTree($this->tree);

        $this->info('Updating...');
        $bar = $this->output->createProgressBar(count($this->sql));
        $bar->start();

        DB::transaction(function () use ($bar) {
            foreach ($this->sql as $query) {
                DB::update($query);
                $bar->advance();
            }
        });

        $bar->finish();

        $this->newLine(1);

        $this->info('Tree rebuild successfully done!');
    }

    public function buildQueriesFromTree($tree, $lft = 0)
    {
        foreach ($tree as $element) {
            if (
                ($element['data']['lft'] != $lft + 1) ||
                ($element['data']['rgt'] != $lft + ($element['children_count'] ?? 0) * 2 + 2) ||
                ($element['data']['distance'] != ($element['children_count'] ?? 0) * 2 + 1) ||
                ($element['data']['depth'] != $element['depth'])
            ) {
                $this->sql[] = sprintf(
                    'UPDATE %s SET lft=%s, rgt=%s, depth=%s, distance=%s, WHERE id=%s',
                    $this->argument('table'),
                    $lft + 1,
                    $lft + ($element['children_count'] ?? 0) * 2 + 2,
                    $element['depth'],
                    ($element['children_count'] ?? 0) * 2 + 1,
                    $element['id']
                );
            }
            $this->buildQueriesFromTree($element['children'], $lft + 1);

            $lft = ($lft + ($element['children_count'] ?? 0) * 2 + 2);
        }
    }

    public function buildNestedSet($elements, $parentId = null, $depth = 1)
    {
        $tree = [];

        if (! isset($elements[$parentId])) {
            return $tree;
        }

        foreach ($elements[$parentId] as $element) {
            $children = $this->buildNestedSet($elements, $element->id, $depth + 1);

            $tree[] = [
                'id' => $element->id,
                'data' => $element,
                'depth' => $depth,
                'children_count' => array_sum(array_column($children, 'children_count')) + count($children),
                'children' => $children,
            ];
        }

        return $tree;
    }
}
