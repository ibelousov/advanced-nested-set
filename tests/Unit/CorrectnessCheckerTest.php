<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Test;
use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Product;
use Ibelousov\AdvancedNestedSet\Utilities\CorrectnessChecker;
use Illuminate\Support\Facades\DB;

class CorrectnessCheckerTest extends TestCase
{
    /** @test */
    public function is_correctly_verified_correct_nested_set()
    {
        $t1 = Test::create();
        $t2 = Test::create(['parent_id' => $t1->id]);
        $t3 = Test::create(['parent_id' => $t2->id]);
        $t4 = Test::create();

        $this->assertTrue(CorrectnessChecker::isCorrect('tests'));
    }

    /** @test */
    public function is_correctly_verified_empty_nested_set()
    {
        $this->assertEquals(0, Test::count());
        $this->assertTrue(CorrectnessChecker::isCorrect('tests'));
    }
}