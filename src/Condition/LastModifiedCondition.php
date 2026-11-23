<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Compares the last modification time (UNIX timestamp) with a fixed value.
 *
 * Items without a known modification time never match.
 */
final readonly class LastModifiedCondition implements SerializableCondition
{
    public function __construct(
        public Comparison $comparison,
        public int $timestamp,
    ) {}

    public function matches(StorageAttributes $item): bool
    {
        $lastModified = $item->lastModified();

        return $lastModified !== null && $this->comparison->compare($lastModified, $this->timestamp);
    }

    public function describe(): string
    {
        return \sprintf('lastModified %s %s', $this->comparison->value, gmdate('Y-m-d\TH:i:s\Z', $this->timestamp));
    }

    public function toArray(): array
    {
        return ['type' => 'lastModified', 'operator' => $this->comparison->value, 'timestamp' => $this->timestamp];
    }
}
