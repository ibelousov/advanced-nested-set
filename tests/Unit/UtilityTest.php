<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Test;
use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Product;
use Ibelousov\AdvancedNestedSet\Utilities\CorrectnessChecker;
use Illuminate\Support\Facades\DB;

class UtilityTest extends TestCase
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
    public function is_correctly_verified_uncorrect_nested_set()
    {
        DB::update("INSERT INTO tests(depth) VALUES(1)");

        $this->assertTrue(!CorrectnessChecker::isCorrect('tests'));

        DB::delete('DELETE FROM tests WHERE id IS NOT NULL');
    }
}