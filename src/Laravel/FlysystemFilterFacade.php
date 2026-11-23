<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Laravel;

use Illuminate\Support\Facades\Facade;
use League\Flysystem\DirectoryListing;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

/**
 * @method static DirectoryListing<StorageAttributes> filter(iterable<mixed> $list, FilterBuilder|Condition|callable(StorageAttributes): bool $builder)
 * @method static FilterBuilder builder()
 *
 * @see FlysystemFilter
 */
final class FlysystemFilterFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlysystemFilter::class;
    }
}
