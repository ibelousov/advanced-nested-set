# Advanced nested set

## Attention

**FOR NOW POSTGRESQL IS ONLY SUPPORTED DATABASE**

## Overview

Super nested set is laravel package, that can be used for handling nested tree sets like this:

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
composer install ibelousov/math-exec
```

## Usage

### Setup

So, you have Category model and wants to add to it nested set. For that purpose you can:

#### 1) Create and execute migration

```php
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
             $table->superNestedSet(); // create columns lft,rgt,depth,parent_id,deleted_at
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
             $table->dropSuperNestedSet(); // delete columns lft,rgt,depth,parent_id,deleted_at
        });
    }
```

#### 2) add AdvancedNestedSet to model:

```php
use \Ibelousov\AdvancedNestedSet\Relations\AdvancedNestedSet;

class Category extends Model
{
    use AdvancedNestedSet;
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

```php
- To move node from parent to root
```

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

### Console commands

#### Fix table

To fix nested set in table you can use:

php artisan advanced-nested-set:fix tablename

You can add this command to schedule to hold nested set in correct state

#### Check table

To check nested set in table you can use:

php artisan advanced-nested-set:check tablename --verbose

### Important

As this package use Cache, to correct handle it between processes for atomic lock it's suggested to use redis/memcached or other inmemory databases for speed and correctnes