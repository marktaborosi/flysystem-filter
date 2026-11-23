<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\Comparison;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\SizeCondition;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Support\Size;

/**
 * Filters on the file size.
 *
 * Sizes are bytes (`1024`) or strings with a binary unit: `'512B'`, `'10K'`, `'10KB'`,
 * `'10KiB'`, `'1.5M'`, `'2 GB'`, `'1T'`. Invalid sizes throw immediately.
 *
 * Only files with a **known** size match: directories and files whose size the adapter
 * did not report never match a size filter (not even `sizeNotEquals()`).
 *
 * @internal used by {@see FilterBuilder}
 */
trait SizeFiltersTrait
{
    /**
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    /**
     * @throws InvalidArgumentException for an invalid size
     */
    public function sizeEquals(int|string $size): static
    {
        return $this->push(new SizeCondition(Comparison::Equal, Size::toBytes($size)));
    }

    /**
     * @throws InvalidArgumentException for an invalid size
     */
    public function sizeNotEquals(int|string $size): static
    {
        return $this->push(new SizeCondition(Comparison::NotEqual, Size::toBytes($size)));
    }

    /**
     * Larger than the size (exclusive).
     *
     * @throws InvalidArgumentException for an invalid size
     */
    public function sizeGt(int|string $size): static
    {
        return $this->push(new SizeCondition(Comparison::GreaterThan, Size::toBytes($size)));
    }

    /**
     * At least the size (inclusive).
     *
     * @throws InvalidArgumentException for an invalid size
     */
    public function sizeGte(int|string $size): static
    {
        return $this->push(new SizeCondition(Comparison::GreaterThanOrEqual, Size::toBytes($size)));
    }

    /**
     * Smaller than the size (exclusive).
     *
     * @throws InvalidArgumentException for an invalid size
     */
    public function sizeLt(int|string $size): static
    {
        return $this->push(new SizeCondition(Comparison::LessThan, Size::toBytes($size)));
    }

    /**
     * At most the size (inclusive).
     *
     * @throws InvalidArgumentException for an invalid size
     */
    public function sizeLte(int|string $size): static
    {
        return $this->push(new SizeCondition(Comparison::LessThanOrEqual, Size::toBytes($size)));
    }

    /**
     * Within the range, both ends inclusive, e.g. `sizeBetween('500K', '5M')`.
     *
     * @throws InvalidArgumentException for an invalid size or when min > max
     */
    public function sizeBetween(int|string $min, int|string $max): static
    {
        $minBytes = Size::toBytes($min);
        $maxBytes = Size::toBytes($max);

        if ($minBytes > $maxBytes) {
            throw new InvalidArgumentException(\sprintf('sizeBetween(): the minimum (%d B) is larger than the maximum (%d B).', $minBytes, $maxBytes));
        }

        return $this->push(new AllOf(
            new SizeCondition(Comparison::GreaterThanOrEqual, $minBytes),
            new SizeCondition(Comparison::LessThanOrEqual, $maxBytes),
        ));
    }

    /**
     * Files with a size of 0 bytes.
     */
    public function isEmptyFile(): static
    {
        return $this->push(new SizeCondition(Comparison::Equal, 0));
    }
}
