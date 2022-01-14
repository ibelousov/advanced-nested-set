<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;

class UpdateTest extends TestCase
{
    /** @test */
    public function can_move_one_down_after_another_test()
    {
        $test1 = Category::create();
        $test2 = Category::create();

        $test1->moveAfter($test2);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test2->fresh()->lft);
        $this->assertEquals(2, $test2->fresh()->rgt);
        $this->assertEquals(3, $test1->fresh()->lft);
        $this->assertEquals(4, $test1->fresh()->rgt);
    }

    /** @test */
    public function can_move_one_up_after_another()
    {
        $test1 = Category::create();
        $test2 = Category::create();

        $test2->moveAfter($test1);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test1->fresh()->lft);
        $this->assertEquals(2, $test1->fresh()->rgt);
        $this->assertEquals(3, $test2->fresh()->lft);
        $this->assertEquals(4, $test2->fresh()->rgt);
    }

    /** @test */
    public function can_move_one_down_after_another_through_third()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create();

        $test1->moveAfter($test3);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test2->fresh()->lft);
        $this->assertEquals(2, $test2->fresh()->rgt);
        $this->assertEquals(3, $test3->fresh()->lft);
        $this->assertEquals(4, $test3->fresh()->rgt);
        $this->assertEquals(5, $test1->fresh()->lft);
        $this->assertEquals(6, $test1->fresh()->rgt);
    }

    /** @test */
    public function can_move_one_up_after_another_through_third_without_hierarchy()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create();

        $test3->moveAfter($test1);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test1->fresh()->lft);
        $this->assertEquals(2, $test1->fresh()->rgt);
        $this->assertEquals(3, $test3->fresh()->lft);
        $this->assertEquals(4, $test3->fresh()->rgt);
        $this->assertEquals(5, $test2->fresh()->lft);
        $this->assertEquals(6, $test2->fresh()->rgt);
    }

    /** @test */
    public function can_move_one_down_after_another_through_third_with_hierarchy()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create(['parent_id' => $test2->id]);
        $test4 = Category::create(['parent_id' => $test3->id]);
        $test5 = Category::create();

        $test1->moveAfter($test5);

        $this->assertTrue(Category::isCorrect());
        $this->assertEquals(1, $test2->fresh()->lft);
        $this->assertEquals(6, $test2->fresh()->rgt);
        $this->assertEquals(2, $test3->fresh()->lft);
        $this->assertEquals(5, $test3->fresh()->rgt);
        $this->assertEquals(3, $test4->fresh()->lft);
        $this->assertEquals(4, $test4->fresh()->rgt);
        $this->assertEquals(7, $test5->fresh()->lft);
        $this->assertEquals(8, $test5->fresh()->rgt);
        $this->assertEquals(9, $test1->fresh()->lft);
        $this->assertEquals(10, $test1->fresh()->rgt);
    }

    /** @test */
    public function can_move_one_up_after_another_through_third_with_hierarchy()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create(['parent_id' => $test2->id]);
        $test4 = Category::create(['parent_id' => $test3->id]);
        $test5 = Category::create();

        $test5->moveAfter($test1);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test1->fresh()->lft);
        $this->assertEquals(2, $test1->fresh()->rgt);
        $this->assertEquals(3, $test5->fresh()->lft);
        $this->assertEquals(4, $test5->fresh()->rgt);
        $this->assertEquals(5, $test2->fresh()->lft);
        $this->assertEquals(10, $test2->fresh()->rgt);
        $this->assertEquals(6, $test3->fresh()->lft);
        $this->assertEquals(9, $test3->fresh()->rgt);
        $this->assertEquals(7, $test4->fresh()->lft);
        $this->assertEquals(8, $test4->fresh()->rgt);
    }

    /** @test */
    public function can_move_one_up_after_another_with_different_parents()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create(['parent_id' => $test2->id]);

        $test3->moveAfter($test1);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test1->fresh()->lft);
        $this->assertEquals(2, $test1->fresh()->rgt);
        $this->assertEquals(3, $test2->fresh()->lft);
        $this->assertEquals(6, $test2->fresh()->rgt);
        $this->assertEquals(4, $test3->fresh()->lft);
        $this->assertEquals(5, $test3->fresh()->rgt);

        $test1->moveAfter($test3);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test1->fresh()->lft);
        $this->assertEquals(2, $test1->fresh()->rgt);
        $this->assertEquals(3, $test2->fresh()->lft);
        $this->assertEquals(6, $test2->fresh()->rgt);
        $this->assertEquals(4, $test3->fresh()->lft);
        $this->assertEquals(5, $test3->fresh()->rgt);
    }

    /** @test */
    public function can_move_one_to_another()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create();

        $test2->update(['parent_id' => $test1->id]);

        $this->assertTrue(Category::isCorrect());
        $this->assertEquals(1, $test1->fresh()->lft);
        $this->assertEquals(4, $test1->fresh()->rgt);
        $this->assertEquals(2, $test2->fresh()->lft);
        $this->assertEquals(3, $test2->fresh()->rgt);
        $this->assertEquals(5, $test3->fresh()->lft);
        $this->assertEquals(6, $test3->fresh()->rgt);
    }

    /** @test */
    public function can_move_first_to_second_and_third_to_first()
    {
        $test1 = Category::create();
        $test2 = Category::create();
        $test3 = Category::create();

        $test1->update(['parent_id' => $test2->id]);
        $test3->update(['parent_id' => $test1->id]);

        $this->assertEquals(true, Category::isCorrect());
        $this->assertEquals(1, $test2->fresh()->lft);
        $this->assertEquals(6, $test2->fresh()->rgt);
        $this->assertEquals(2, $test1->fresh()->lft);
        $this->assertEquals(5, $test1->fresh()->rgt);
        $this->assertEquals(3, $test3->fresh()->lft);
        $this->assertEquals(4, $test3->fresh()->rgt);
    }
}
