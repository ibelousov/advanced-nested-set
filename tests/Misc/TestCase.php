<?php


namespace Ibelousov\AdvancedNestedSet\Tests\Misc;

use Ibelousov\AdvancedNestedSet\Relations\AdvancedNestedSet;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Product;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}