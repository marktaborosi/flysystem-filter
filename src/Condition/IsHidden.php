<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Matches "dot" entries: items whose name starts with a dot (`.env`, `.git`)
 * **or** that live anywhere inside a dot directory (`.git/config`).
 */
final readonly class IsHidden implements SerializableCondition
{
    public function matches(StorageAttributes $item): bool
    {
        foreach (explode('/', trim($item->path(), '/')) as $segment) {
            if ($segment !== '.' && $segment !== '..' && str_starts_with($segment, '.')) {
                return true;
            }
        }

        return false;
    }

    public function describe(): string
    {
        return 'is hidden';
    }

    public function toArray(): array
    {
        return ['type' => 'isHidden'];
    }
}
