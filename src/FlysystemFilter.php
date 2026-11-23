<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter;

use Generator;
use League\Flysystem\DirectoryListing;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Exception\InvalidFilterException;

/**
 * Applies a filter to a Flysystem directory listing.
 *
 * Since v4 the builder is also directly usable with Flysystem: `$listing->filter($builder)`.
 * This class remains as a convenient, container-friendly entry point (and backs the Laravel facade).
 */
final class FlysystemFilter
{
    /**
     * Returns a lazily filtered listing that only contains matching {@see StorageAttributes}.
     * Entries that are not StorageAttributes are skipped.
     *
     * The filter is validated (and compiled) immediately, so a malformed builder throws here
     * rather than while iterating. Like any generator-backed DirectoryListing, the result can
     * be iterated once; call `toArray()` to reuse it.
     *
     * @param iterable<mixed> $list
     * @param FilterBuilder|Condition|callable(StorageAttributes): bool $builder
     *
     * @return DirectoryListing<StorageAttributes>
     *
     * @throws InvalidFilterException when the builder is incomplete
     */
    public function filter(iterable $list, FilterBuilder|Condition|callable $builder): DirectoryListing
    {
        if ($builder instanceof FilterBuilder) {
            $builder = $builder->toCondition();
        }

        $predicate = $builder instanceof Condition ? $builder->matches(...) : $builder(...);

        $filtered = (static function () use ($list, $predicate): Generator {
            foreach ($list as $item) {
                if ($item instanceof StorageAttributes && $predicate($item)) {
                    yield $item;
                }
            }
        })();

        return new DirectoryListing($filtered);
    }

    /**
     * Starts a new builder, handy through the Laravel facade: `FlysystemFilter::builder()->isFile()`.
     */
    public function builder(): FilterBuilder
    {
        return new FilterBuilder();
    }
}
