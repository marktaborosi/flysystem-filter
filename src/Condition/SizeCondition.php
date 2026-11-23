<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Support\Size;

/**
 * Compares the file size (in bytes) with a fixed value.
 *
 * Only files with a **known** size can match: directories and files whose size
 * was not provided by the adapter (`fileSize() === null`) never match, not even
 * for {@see Comparison::NotEqual}.
 */
final readonly class SizeCondition implements SerializableCondition
{
    public function __construct(
        public Comparison $comparison,
        public int $bytes,
    ) {}

    public function matches(StorageAttributes $item): bool
    {
        if (!$item instanceof FileAttributes) {
            return false;
        }

        $size = $item->fileSize();

        return $size !== null && $this->comparison->compare($size, $this->bytes);
    }

    public function describe(): string
    {
        return \sprintf('size %s %s', $this->comparison->value, Size::format($this->bytes));
    }

    public function toArray(): array
    {
        return ['type' => 'size', 'operator' => $this->comparison->value, 'bytes' => $this->bytes];
    }
}
