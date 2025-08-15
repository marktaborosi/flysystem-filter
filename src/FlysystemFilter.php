<?php

namespace Marktaborosi\FlysystemFilter;

use League\Flysystem\DirectoryListing;
use League\Flysystem\StorageAttributes;

/**
 * Provides filtering for Flysystem directory listings.
 *
 * Accepts either a {@see FilterBuilder} instance or a callable predicate to determine
 * which {@see StorageAttributes} items from the given {@see DirectoryListing} should be returned.
 * Non-{@see StorageAttributes} entries in the listing are automatically skipped.
 *
 * @package Marktaborosi\FlysystemFilter
 */
class FlysystemFilter
{
    /**
     * Filters a directory listing using the given builder or predicate.
     *
     * @param DirectoryListing       $list    The directory listing to filter.
     * @param callable|FilterBuilder $builder A {@see FilterBuilder} instance or a callable
     *                                        `(StorageAttributes $item): bool` to decide inclusion.
     *
     * @return DirectoryListing A new listing containing only the matching {@see StorageAttributes} items.
     */
    public function filter(DirectoryListing $list, callable|FilterBuilder $builder): DirectoryListing
    {
        if ($builder instanceof FilterBuilder) {
            $predicate = function ($item) use ($builder) {
                return $item instanceof StorageAttributes && $builder->matches($item);
            };
        } else {
            $predicate = function ($item) use ($builder) {
                return $item instanceof StorageAttributes && $builder($item);
            };
        }

        $filteredGenerator = (function () use ($list, $predicate) {
            foreach ($list as $attribute) {
                if ($predicate($attribute)) {
                    yield $attribute;
                }
            }
        })();

        return new DirectoryListing($filteredGenerator);
    }
}
