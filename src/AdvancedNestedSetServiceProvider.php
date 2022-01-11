<?php

namespace Ibelousov\AdvancedNestedSet;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Ibelousov\AdvancedNestedSet\Console\Fix;
use Ibelousov\AdvancedNestedSet\Console\Check;
use Illuminate\Database\Schema\Blueprint;

class AdvancedNestedSetServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if($this->app->runningInConsole()) 
            $this->commands(
                Fix::class,
                Check::class
            );

    }

    public function register()
    {
        Blueprint::macro('advancedNestedSet', function () {
            MigrationMacro::columns($this);
        });

        Blueprint::macro('dropAdvancedNestedSet', function () {
            MigrationMacro::dropColumns($this);
        });
    }
}