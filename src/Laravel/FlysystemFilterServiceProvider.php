<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

/**
 * Registers {@see FlysystemFilter} in the Laravel container (Laravel 11, 12 and 13).
 *
 * Resolve it by type-hinting `FlysystemFilter`, via `app('flysystem.filter')` or the facade.
 */
final class FlysystemFilterServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(FlysystemFilter::class);
        $this->app->alias(FlysystemFilter::class, 'flysystem.filter');
    }

    /**
     * @return list<string>
     */
    public function provides(): array
    {
        return [FlysystemFilter::class, 'flysystem.filter'];
    }
}
