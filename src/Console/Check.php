<?php

namespace Ibelousov\AdvancedNestedSet\Console;

use Ibelousov\AdvancedNestedSet\Utilities\CorrectnessChecker;
use Illuminate\Console\Command;

class Check extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advanced-nested-set:check {table}';

    protected $elements;
    protected $elementsGrouped;
    protected $errorsCount = 0;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if NestedSet is correct in table';

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
        try {
            $errors = CorrectnessChecker::getErrors($this->argument('table'));

            if (count($errors)) {
                $this->error(sprintf('Tree is broken, errors count: %s', count($errors)));
            } else {
                $this->info('Tree is ok');
            }

            if ($this->option('verbose')) {
                foreach ($errors as $error) {
                    $this->error($error);
                }
            }
        } catch (\Exception $exception) {
            return $this->error('Table "'.$this->argument('table')."\" doesn't exists, or it's not a NestedSet");
        }

        return 0;
    }
}
