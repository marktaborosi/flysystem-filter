<?php

namespace Marktaborosi\FlysystemFilter;

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;

class FlysystemFilter
{

    /**
     * @param DirectoryListing $list
     * @param FilterBuilder $builder
     * @return DirectoryListing
     */
    public static function filter(DirectoryListing $list, FilterBuilder $builder): DirectoryListing
    {
        $filteredList = [];
        foreach ($list->toArray() as $attribute) {
            if ($attribute instanceof StorageAttributes && $builder->matches($attribute)) {
                $filteredList[] = $attribute;
            }
        }
        return new DirectoryListing($filteredList);
    }
}
