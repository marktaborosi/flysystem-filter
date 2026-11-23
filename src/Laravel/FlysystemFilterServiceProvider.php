<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Laravel;

use Closure;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

/**
 * Registers {@see FlysystemFilter} in the Laravel container and the `filter()` macro
 * on Laravel disks (Laravel 11, 12 and 13).
 *
 * ```php
 * Storage::disk('s3')->filter('uploads')->isImage()->paths();
 * ```
 */
final class FlysystemFilterServiceProvider extends ServiceProvider
{
    /**
     * Name of the disk macro. Another package that already registered a macro with this
     * name keeps it; use {@see StorageFilter::on()} in that case.
     */
    public const MACRO = 'filter';

    public function register(): void
    {
        $this->app->singleton(FlysystemFilter::class);
        $this->app->alias(FlysystemFilter::class, 'flysystem.filter');
    }

    public function boot(): void
    {
        if (FilesystemAdapter::hasMacro(self::MACRO) || method_exists(FilesystemAdapter::class, self::MACRO)) {
            return;
        }

        self::registerDiskMacro(self::MACRO, function (string $directory = '', bool $recursive = true, ?FilterBuilder $builder = null): StorageFilter {
            return StorageFilter::on($this, $directory, $recursive, $builder);
        });
    }

    /**
     * @param-closure-this FilesystemAdapter $macro
     */
    private static function registerDiskMacro(string $name, Closure $macro): void
    {
        FilesystemAdapter::macro($name, $macro);
    }
}
