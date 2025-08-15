<?php

namespace Marktaborosi\FlysystemFilter\Laravel;

use Illuminate\Support\Facades\Facade;
use League\Flysystem\DirectoryListing;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * @method static DirectoryListing filter(DirectoryListing $list, FilterBuilder $builder)
 */
class FlysystemFilterFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'flysystem.filter';
    }
}