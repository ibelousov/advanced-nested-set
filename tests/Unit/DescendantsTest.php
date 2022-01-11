<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Product;
use Illuminate\Support\Facades\DB;

class DescendantsTest extends TestCase
{
    /** @test */
    public function is_relation_works()
    {
        $c1 = Category::create();
        $c2 = Category::create(['parent_id' => $c1->id]);
        $c3 = Category::create(['parent_id' => $c2->id]);

        $this->assertEquals([$c2->id,$c3->id], $c1->fresh()->descendants->pluck('id')->toArray());
    }

    /** @test */
    public function is_where_has_works()
    {
        $t1 = Category::create(['name' => 'root']);
        $t2 = Category::create(['name' => 'parent', 'parent_id' => $t1->id]);
        $t3 = Category::create(['name' => 'child', 'parent_id' => $t2->id]);

        $results1 = Category::whereHas('descendants', fn($q) => $q->where('name', '=', 'child'))->get();
        $results2 = Category::whereHas('descendants', fn($q) => $q->where('name', '=', 'parent'))->get();
        $results3 = Category::whereHas('descendants', fn($q) => $q->where('name', '=', 'root'))->get();

        $this->assertEquals(2, $results1->count());
        $this->assertEquals([$t1->id,$t2->id], $results1->pluck('id')->toArray());
        $this->assertEquals(1, $results2->count());
        $this->assertEquals([$t1->id], $results2->pluck('id')->toArray());
        $this->assertEquals(0, $results3->count());
    }

    /** @test */
    public function is_where_has_dotted_works()
    {
        $category = Category::create(['name' => 'category']);
        $subCategory = Category::create(['name' => 'subcategory', 'parent_id' => $category->id]);
        $subCategory->products()->create(['name' => 'product']);

        $results = Category::whereHas('descendants.products', fn($q) => $q->where('name', 'product'))->get();

        $this->assertEquals([$category->id], $results->pluck('id')->toArray());
    }

    /** @test */
    public function is_has_works()
    {
        $quantity = 10;
        $parentId = null;
        $categories = [];
        foreach (range(0, $quantity) as $i)
            $parentId = ($categories[] = Category::create(['parent_id' => $parentId]))->id;

        $categories[array_key_last($categories) - 1]->products()->create();

        foreach(range($quantity, 0, -1) as $i)
            $this->assertEquals($quantity, Category::has('descendants')->count());
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

        foreach ($categories as $key => $category)
            $this->assertEquals($quantity - $key, Category::withCount('descendants')->get()->skip($key)->first()->descendants_count);
    }
}