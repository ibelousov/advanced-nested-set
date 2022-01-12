<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Misc;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }
}
