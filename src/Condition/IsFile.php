<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;

/**
 * Matches file entries ({@see FileAttributes}).
 */
final readonly class IsFile implements SerializableCondition
{
    public function matches(StorageAttributes $item): bool
    {
        return $item instanceof FileAttributes;
    }

    public function describe(): string
    {
        return 'is file';
    }

    public function toArray(): array
    {
        return ['type' => 'isFile'];
    }
}
