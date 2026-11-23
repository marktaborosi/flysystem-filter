<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Compares the depth of the item's path: the number of `/` separators after
 * trimming leading and trailing slashes (`file.txt` → 0, `a/b/file.txt` → 2).
 */
final readonly class DepthCondition implements SerializableCondition
{
    public function __construct(
        public Comparison $comparison,
        public int $depth,
    ) {}

    public static function depthOf(string $path): int
    {
        return substr_count(trim($path, '/'), '/');
    }

    public function matches(StorageAttributes $item): bool
    {
        return $this->comparison->compare(self::depthOf($item->path()), $this->depth);
    }

    public function describe(): string
    {
        return \sprintf('depth %s %d', $this->comparison->value, $this->depth);
    }

    public function toArray(): array
    {
        return ['type' => 'depth', 'operator' => $this->comparison->value, 'depth' => $this->depth];
    }
}
