<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;

class DescendantsAndSelfTest extends TestCase
{
    /** @test */
    public function is_relation_works()
    {
        $c1 = Category::create();
        $c2 = Category::create(['parent_id' => $c1->id]);
        $c3 = Category::create(['parent_id' => $c2->id]);

        $this->assertEquals([$c1->id, $c2->id, $c3->id], $c1->fresh()->descendants_and_self->pluck('id')->toArray());
    }

    /** @test */
    public function is_where_has_works()
    {
        $t1 = Category::create(['name' => 'root']);
        $t2 = Category::create(['name' => 'parent', 'parent_id' => $t1->id]);
        $t3 = Category::create(['name' => 'child', 'parent_id' => $t2->id]);
        $t4 = Category::create();

        $results1 = Category::whereHas('descendants_and_self', fn ($q) => $q->where('name', '=', 'child'))->get();
        $results2 = Category::whereHas('descendants_and_self', fn ($q) => $q->where('name', '=', 'parent'))->get();
        $results3 = Category::whereHas('descendants_and_self', fn ($q) => $q->where('name', '=', 'root'))->get();

        $this->assertEquals(3, $results1->count());
        $this->assertEquals([$t1->id, $t2->id, $t3->id], $results1->pluck('id')->toArray());
        $this->assertEquals(2, $results2->count());
        $this->assertEquals([$t1->id, $t2->id], $results2->pluck('id')->toArray());
        $this->assertEquals(1, $results3->count());
        $this->assertEquals([$t1->id], $results3->pluck('id')->toArray());
    }

    /** @test */
    public function is_where_has_dotted_works()
    {
        $category = Category::create(['name' => 'category']);
        $category->products()->create(['name' => 'product']);
        $subCategory = Category::create(['name' => 'subcategory', 'parent_id' => $category->id]);
        $subCategory->products()->create(['name' => 'product']);

        $results = Category::whereHas('descendants_and_self.products', fn ($q) => $q->where('name', 'product'))->get();

        $this->assertEquals([$category->id, $subCategory->id], $results->pluck('id')->toArray());
    }

    /** @test */
    public function is_has_works()
    {
        $quantity = 10;
        $parentId = null;
        $categories = [];
        foreach (range(0, $quantity) as $i) {
            $parentId = ($categories[] = Category::create(['parent_id' => $parentId]))->id;
        }

        $categories[array_key_last($categories) - 1]->products()->create();

        foreach (range($quantity, 0, -1) as $i) {
            $this->assertEquals($quantity + 1, Category::has('descendants_and_self')->count());
        }
    }

    /** @test */
    public function is_with_count_works()
    {
        $quantity = 10;
        $parentId = null;
        $categories = [];

        foreach (range(0, $quantity) as $i) {
            $category = Category::create(['parent_id' => $parentId]);
            $categories[] = $category;
            $parentId = $category->id;
        }

        foreach ($categories as $key => $category) {
            $this->assertEquals($quantity - $key + 1, Category::withCount('descendants_and_self')->get()->skip($key)->first()->descendants_and_self_count);
        }
    }
}
