<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\StorageAttributes;

/**
 * Matches directory entries ({@see DirectoryAttributes}).
 */
final readonly class IsDirectory implements SerializableCondition
{
    public function matches(StorageAttributes $item): bool
    {
        return $item instanceof DirectoryAttributes;
    }

    public function describe(): string
    {
        return 'is directory';
    }

    public function toArray(): array
    {
        return ['type' => 'isDirectory'];
    }
}
