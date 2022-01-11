<?php


namespace Ibelousov\AdvancedNestedSet\Tests\Unit;


use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Utilities\CorrectnessChecker;

class DeleteTest extends TestCase
{
    /** @test */
    public function can_delete_one_item()
    {
        $test = Category::create();
        Category::create();
        Category::create();
        Category::create();
        Category::create();

        $test->delete();

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(4, Category::count());
    }

    /** @test */
    public function can_delete_many_items()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create();
        $test4 = Category::create();
        Category::create();

        $test1->delete();
        $test2->delete();
        $test3->delete();
        $test4->delete();

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, Category::count());
    }

    /** @test */
    public function can_delete_nested_items()
    {
        $test1 = Category::create();
        $test2 = Category::create(['parent_id' => $test1->id]);
        $test3 = Category::create(['parent_id' => $test2->id]);
        $test4 = Category::create(['parent_id' => $test3->id]);

        $test1->fresh()->delete();

        $this->assertEquals(0, Category::count());
        $this->assertEquals(true, Category::isCorrect());
    }
}