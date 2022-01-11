<?php


namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;
use Ibelousov\AdvancedNestedSet\Utilities\CorrectnessChecker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use phpDocumentor\Reflection\Types\Void_;

class CreateTest extends TestCase
{
    /** @test */
    public function is_create_works()
    {
        Category::create();

        $this->assertEquals(true, Category::isCorrect());
    }

    /** @test */
    public function is_many_create_works()
    {
        Category::create();
        Category::create();
        Category::create();
        Category::create();
        Category::create();
        Category::create();
        Category::create();

        $this->assertEquals(true, Category::isCorrect());
    }

    /** @test */
    public function is_create_in_parent_works()
    {
        $test1 = Category::create();
        $test2 = Category::create(['parent_id' => $test1->id]);
        $test3 = Category::create(['parent_id' => $test2->id]);
        $test4 = Category::create([]);

        $this->assertEquals(true, Category::isCorrect());

        $this->assertEquals(1, $test1->fresh()->lft);
        $this->assertEquals(6, $test1->fresh()->rgt);
        $this->assertEquals(2, $test2->fresh()->lft);
        $this->assertEquals(5, $test2->fresh()->rgt);
        $this->assertEquals(3, $test3->fresh()->lft);
        $this->assertEquals(4, $test3->fresh()->rgt);
        $this->assertEquals(7, $test4->fresh()->lft);
        $this->assertEquals(8, $test4->fresh()->rgt);
    }

}