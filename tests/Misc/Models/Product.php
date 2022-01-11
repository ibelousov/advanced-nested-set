<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Misc\Models;

use Ibelousov\AdvancedNestedSet\Relations\AdvancedNestedSet;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name','category_id'];

    public $timestamps = false;

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}