<?php


namespace Ibelousov\AdvancedNestedSet\Tests\Misc;

use Ibelousov\AdvancedNestedSet\Relations\AdvancedNestedSet;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Product;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Product::truncate();
        Category::truncate();
        Test::truncate();
    }

}