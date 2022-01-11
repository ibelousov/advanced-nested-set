<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Product;
use Illuminate\Support\Facades\DB;

class ParentsTest extends TestCase
{
    /** @test */
    public function is_relation_works()
    {
        $c1 = Category::create();
        $c2 = Category::create(['parent_id' => $c1->id]);
        $c3 = Category::create(['parent_id' => $c2->id]);

        $this->assertEquals([$c1->id,$c2->id], $c3->fresh()->parents->pluck('id')->toArray());
    }

    /** @test */
    public function is_where_has_works()
    {
       $t1 = Category::create(['name' => 'parentparent']);
       $t2 = Category::create(['name' => 'parent', 'parent_id' => $t1->id]);
       $t3 = Category::create(['name' => 'child', 'parent_id' => $t2->id]);

       $results1 = Category::whereHas('parents', fn($q) => $q->where('name', '=', 'parent'))->get();
       $results2 = Category::whereHas('parents', fn($q) => $q->where('name', 'like', '%parent%'))->get();
       $results3 = Category::whereHas('parents', fn($q) => $q->where('name', '=', 'lorem'))->get();

       $this->assertEquals(1, $results1->count());
       $this->assertEquals($t3->id, $results1->first()->id);
       $this->assertEquals(2, $results2->count());
       $this->assertContains($t2->id, $results2->pluck('id')->toArray());
       $this->assertContains($t3->id, $results2->pluck('id')->toArray());
       $this->assertEquals(0, $results3->count());
    }

    /** @test */
    public function is_where_has_dotted_works()
    {
        $category = Category::create(['name' => 'category']);
        $subCategory = Category::create(['name' => 'subcategory', 'parent_id' => $category->id]);
        $subSubCategory = Category::create(['name' => 'subsubcategory', 'parent_id' => $subCategory->id]);

        $product1 = $subSubCategory->products()->create(['name' => 'product']);
        $product2 = $subCategory->products()->create(['name' => 'product2']);

        $results1 = Product::where('name','=','product')->whereHas('category.parents', fn($q) => $q->where('name', '=', 'category'))->get();
        $results2 = Product::whereHas('category.parents.products', fn($q) => $q->where('name', '=', 'product2'))->get();

        $this->assertEquals(1, $results1->count());
        $this->assertEquals($product1->name, $results1->first()->name);
        $this->assertEquals(1, $results2->count());
        $this->assertEquals($product1->name, $results1->first()->name);
    }

    /** @test */
    public function is_has_works()
    {
        $parentId = null;
        $categories = [];
        foreach(range(0,10) as $i)
            $parentId = ($categories[] = Category::create(['parent_id' => $parentId]))->id;

        $categories[array_key_last($categories)-1]->products()->create();

        $this->assertEquals(count($categories) - 1, Category::has('parents')->count());
        $this->assertEquals(count($categories) - 2, Category::has('parents.parents')->count());
        $this->assertEquals(count($categories) - 3, Category::has('parents.parents.parents')->count());
        $this->assertEquals(1, Category::has('parents.products')->count());
    }

    /** @test */
    public function is_with_count_works()
    {
        $parentId = null;
        $categories = [];

        foreach(range(0,10) as $i) {
            $category = Category::create(['parent_id' => $parentId]);
            $categories[] = $category;
            $parentId = $category->id;
        }

        foreach($categories as $key => $category)
            $this->assertEquals($key, Category::withCount('parents')->get()->skip($key)->first()->parents_count);
    }
}