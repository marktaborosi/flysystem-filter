<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\Callback;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\SerializableCondition;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Custom predicates.
 *
 * @internal used by {@see FilterBuilder}
 */
trait CustomFilterTrait
{
    /**
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    /**
     * Adds a user defined predicate.
     *
     * ```php
     * $filter->custom(fn (StorageAttributes $item): bool => strlen($item->path()) > 10, 'long path');
     * ```
     *
     * Filters with custom predicates cannot be exported with `toArray()`; implement
     * {@see SerializableCondition} for that.
     *
     * @param callable(StorageAttributes): bool $callback
     * @param string|null $description shown by `describe()`
     */
    public function custom(callable $callback, ?string $description = null): static
    {
        return $this->push(new Callback($callback, $description ?? 'custom callback'));
    }
}
