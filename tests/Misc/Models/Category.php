<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Misc\Models;

use Ibelousov\AdvancedNestedSet\AdvancedNestedSet;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use AdvancedNestedSet;

    protected $fillable = ['name', 'lft', 'rgt', 'depth', 'parent_id'];

    public $timestamps = false;

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
