# Advanced nested set

## Attention

**FOR NOW POSTGRESQL, MYSQL AND SQLITE IS ONLY SUPPORTED DATABASES**

## Overview

Advanced nested set is laravel package, that can be used for handling nested tree sets like this:

```php
Category::first()->descendants; // print all nested categories of category
```

or like this

```php
Category::whereHas('parents', function($query) {
    $query->where('name', 'vegetables');
});
```

## Installation

Use the package manager composer to install it

```sh
composer require ibelousov/advanced-nested-set
```

## Usage

### Setup
So, you have Category model and want to add a nested set to it. For that purpose you can:

#### 1) Create and execute migration

```php
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
             $table->advancedNestedSet(); // create columns lft,rgt,depth,parent_id,deleted_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
             $table->dropAdvancedNestedSet(); // delete columns lft,rgt,depth,parent_id,deleted_at
        });
    }
```

#### 2) add AdvancedNestedSet to model:

```php
use \Ibelousov\AdvancedNestedSet\AdvancedNestedSet;

class Category extends Model
{
    use AdvancedNestedSet;

    protected $fillable = [/*...Your columns..*/ 'lft','rgt','parent_id','depth','distance'];
}
```

#### 3) fix nested set(if there are records in Category)

```php
    php artisan advanced-nested-set:fix categories
```

#### 4) if already used "ADVANCED_NESTED_SET_LOCK_" prefix for blocking on CRUD doesn't fit your needs:

add to .env
```php
ADVANCED_NESTED_LOCK_NAME="NEW_LOCK_NAME"
```  

#### 5) Fine tune time for blocking

add to .env
```php
ADVANCED_NESTED_LOCK_WAIT=100 # Seconds waiting for atomic blocking(DEFAULT: 30)
ADVANCED_NESTED_LOCK_DELAY=9999999 # Microseconds waiting after blocking(DEFAULT: 10000)
```

### Create/Update/Delete nodes

- To create node:

```php
$node = Category::create([...]); // Root node
```

- To create node inside parent::
```php
Category::create(['parent_id' => $node->id]); // Child of Root node
```

- To move node from parent to root

```php
$category->update(['parent_id' => null]);
```

- To move node to another parent

```php
$category->update(['parent_id' => $parentId]);
```

- To move node **within** one parent

```php
$category->moveAfter($category2);
```

### Relations

```php
// descendants of category
Category::first()->descendants;
``` 

```php
// descendants of category and category itself
Category::first()->descendants_and_self;
```

```php
// parents of category
Category::first()->parents;
```

```php
// parents and self category of category
Category::first()->parents_and_self;
```

```php
// parent of category
Category::first()->parent;
```

```php
// children of category
Category::first()->children;
```

### Methods

```php
// Is nested set correct
Category::isCorrect();
```

```php
// Get errors
Category::errors();
```

```php
// Convert to treeStructure
Category::get()->toTree();

// Convert to tree and than to array
Category::get()->toTree()->toArray();
```

### Console commands

#### Fix table

To fix nested set in table you can use:

php artisan advanced-nested-set:fix tablename

You can add this command to schedule to hold nested set in correct state

#### Check table

To check nested set in table you can use:

php artisan advanced-nested-set:check tablename --verbose

### Important

As this package uses Cache, for correct handling between atomic lock processes  it's suggested to use redis/memcached or other inmemory databases for speed and accuracy 