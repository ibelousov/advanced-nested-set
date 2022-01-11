<?php

namespace Ibelousov\AdvancedNestedSet\Tests\Misc\Models;

use Ibelousov\AdvancedNestedSet\AdvancedNestedSet;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use AdvancedNestedSet;

    protected $fillable = ['name', 'lft', 'rgt', 'depth', 'parent_id'];

    public $timestamps = false;
}
