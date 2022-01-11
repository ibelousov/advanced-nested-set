<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Unit;

use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Category;
use Ibelousov\AdvancedNestedSet\Tests\Misc\Models\Product;
use Ibelousov\AdvancedNestedSet\Tests\Misc\TestCase;

class ParentsAndSelfTest extends TestCase
{
    /** @test */
    public function is_relation_works()
    {
        $c1 = Category::create();
        $c2 = Category::create(['parent_id' => $c1->id]);
        $c3 = Category::create(['parent_id' => $c2->id]);

        $this->assertEquals([$c1->id, $c2->id, $c3->id], $c3->fresh()->parents_and_self->pluck('id')->toArray());
    }

    /** @test */
    public function is_where_has_works()
    {
        $t1 = Category::create(['name' => 'parent']);
        $t2 = Category::create(['name' => 'child', 'parent_id' => $t1->id]);

        $results1 = Category::whereHas('parents_and_self', fn ($q) => $q->where('name', '=', 'parent'))->get();
        $results2 = Category::whereHas('parents_and_self', fn ($q) => $q->where('name', '=', 'child'))->get();

        $this->assertEquals(2, $results1->count());
        $this->assertEquals($t1->name, $results1->first()->name);
        $this->assertEquals($t2->name, $results1->skip(1)->first()->name);
        $this->assertEquals(1, $results2->count());
        $this->assertEquals($t2->name, $results2->first()->name);
    }

    /** @test */
    public function is_where_has_dotted_works()
    {
        $category = Category::create(['name' => 'category']);
        $subCategory = Category::create(['name' => 'subcategory', 'parent_id' => $category->id]);
        $subSubCategory = Category::create(['name' => 'subsubcategory', 'parent_id' => $subCategory->id]);

        $product1 = $subSubCategory->products()->create(['name' => 'product']);
        $product2 = $subCategory->products()->create(['name' => 'product2']);

        $results1 = Product::where('name', '=', 'product')->whereHas('category.parents_and_self', fn ($q) => $q->where('name', '=', 'category'))->get();
        $results2 = Product::whereHas('category.parents_and_self.products', fn ($q) => $q->where('name', '=', 'product2'))->get();

        $this->assertEquals(1, $results1->count());
        $this->assertEquals($product1->name, $results1->first()->name);
        $this->assertEquals(2, $results2->count());
        $this->assertEquals($product1->name, $results1->first()->name);
    }

    /** @test */
    public function is_has_works()
    {
        $parentId = null;
        $categories = [];
        foreach (range(0, 10) as $i) {
            $parentId = ($categories[] = Category::create(['parent_id' => $parentId]))->id;
        }

        $categories[array_key_last($categories) - 1]->products()->create();

        $this->assertEquals(count($categories), Category::has('parents_and_self')->count());
        $this->assertEquals(count($categories), Category::has('parents_and_self.parents_and_self')->count());
        $this->assertEquals(count($categories), Category::has('parents_and_self.parents_and_self.parents_and_self')->count());
        $this->assertEquals(2, Category::has('parents_and_self.products')->count());
    }
}
