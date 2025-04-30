<?php

namespace Marktaborosi\FlysystemFilter;

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;

class FlysystemFilter
{

    /**
     * Filters a DirectoryListing using the specified FilterBuilder criteria.
     *
     * This method iterates over the provided DirectoryListing and yields only those
     * StorageAttributes instances that satisfy the conditions defined in the FilterBuilder.
     * The result is returned as a new DirectoryListing instance backed by a generator,
     * ensuring memory efficiency even with large datasets.
     *
     * @param DirectoryListing $list    The original directory listing to be filtered.
     * @param FilterBuilder    $builder The filter builder containing the filtering criteria.
     *
     * @return DirectoryListing A new DirectoryListing instance containing only the items
     *                          that match the specified filter criteria.
     */
    public static function filter(DirectoryListing $list, FilterBuilder $builder): DirectoryListing
    {
        $filteredGenerator = (function () use ($list, $builder) {
            foreach ($list as $attribute) {
                if ($attribute instanceof StorageAttributes && $builder->matches($attribute)) {
                    yield $attribute;
                }
            }
        })();

        return new DirectoryListing($filteredGenerator);
    }
}
