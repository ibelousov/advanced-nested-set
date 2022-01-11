<?php

namespace Ibelousov\AdvancedNestedSet;

use Ibelousov\AdvancedNestedSet\Console\Check;
use Ibelousov\AdvancedNestedSet\Console\Fix;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AdvancedNestedSetServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                Fix::class,
                Check::class
            );
        }
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
