<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait CustomFilterTrait
 *
 * Provides support for registering user-defined custom filtering logic
 * within the Flysystem FilterBuilder.
 *
 * This trait allows developers to inject arbitrary logic using a
 * closure or callable that receives a `StorageAttributes` object.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 *
 * @mixin FilterBuilder
 */
trait CustomFilterTrait
{
    /**
     * Register a custom user-defined filtering condition.
     *
     * This method accepts any callable that receives a `StorageAttributes` instance and
     * returns a boolean indicating whether the item should be included in the result.
     *
     * Example usage:
     * ```php
     * $filter->custom(fn(StorageAttributes $item) => strlen($item->path()) > 10);
     * ```
     *
     * @param callable(StorageAttributes): bool $callback
     * @return FilterBuilder|CustomFilterTrait
     */
    public function custom(callable $callback): self
    {
        $this->addCondition($callback);
        return $this;
    }
}