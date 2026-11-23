<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Matches items whose adapter-specific extra metadata (`extraMetadata()`) contains the given key,
 * e.g. `ETag`, `StorageClass` on S3.
 */
final readonly class HasMetadata implements SerializableCondition
{
    public function __construct(public string $key) {}

    public function matches(StorageAttributes $item): bool
    {
        return \array_key_exists($this->key, $item->extraMetadata());
    }

    public function describe(): string
    {
        return \sprintf('has metadata "%s"', $this->key);
    }

    public function toArray(): array
    {
        return ['type' => 'hasMetadata', 'key' => $this->key];
    }
}
