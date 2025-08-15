<?php

namespace Marktaborosi\FlysystemFilter\Laravel;

use Illuminate\Support\ServiceProvider;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

class FlysystemFilterServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton('flysystem.filter', function () {
            return new FlysystemFilter();
        });
    }

}