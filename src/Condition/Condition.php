<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * A single, immutable predicate that decides whether a Flysystem item matches.
 *
 * Every built-in filter of the {@see FilterBuilder} is
 * represented by a Condition object, and the builder itself compiles into a tree of
 * conditions ({@see AllOf}, {@see AnyOf}, {@see Not}). Implement this interface to
 * create your own reusable filters and pass them to `FilterBuilder::where()`.
 */
interface Condition
{
    /**
     * Returns true when the given item satisfies this condition.
     */
    public function matches(StorageAttributes $item): bool;

    /**
     * Returns a short, human-readable description, e.g. `extension equals [txt, md]`.
     */
    public function describe(): string;
}
