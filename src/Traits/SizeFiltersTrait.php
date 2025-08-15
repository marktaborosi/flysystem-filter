<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait SizeFiltersTrait
 *
 * Provides filtering methods based on file size, using the `fileSize()` metadata
 * from Flysystem's `FileAttributes`.
 *
 * Filters accept either byte values as integers, or human-readable strings with units:
 * - `'512B'`, `'2K'`, `'5M'`, `'1G'`, `'3T'`, etc.
 *
 * Non-file entries (e.g., directories) are automatically excluded.
 *
 * This trait is designed to be used with {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait SizeFiltersTrait
{
    /**
     * Filter files with an exact size in bytes.
     *
     * @param int|string $size Size to match (e.g., `1024` or `'1K'`).
     * @return FilterBuilder|SizeFiltersTrait
     */
    public function sizeEquals(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            return $item instanceof FileAttributes && $item->fileSize() === $sizeInBytes;
        });
        return $this;
    }

    /**
     * Filter files that do not have the specified size.
     *
     * @param int|string $size Size to exclude.
     * @return FilterBuilder|SizeFiltersTrait
     */
    public function sizeNotEquals(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            return $item instanceof FileAttributes && $item->fileSize() !== $sizeInBytes;
        });
        return $this;
    }

    /**
     * Filter files with a size greater than the specified value.
     *
     * @param int|string $size Minimum size (exclusive).
     * @return FilterBuilder|SizeFiltersTrait
     */
    public function sizeGt(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            return $item instanceof FileAttributes && $item->fileSize() > $sizeInBytes;
        });
        return $this;
    }

    /**
     * Filter files with a size greater than or equal to the specified value.
     *
     * @param int|string $size Minimum size (inclusive).
     * @return FilterBuilder|SizeFiltersTrait
     */
    public function sizeGte(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            return $item instanceof FileAttributes && $item->fileSize() >= $sizeInBytes;
        });
        return $this;
    }

    /**
     * Filter files with a size less than the specified value.
     *
     * @param int|string $size Maximum size (exclusive).
     * @return FilterBuilder|SizeFiltersTrait
     */
    public function sizeLt(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            return $item instanceof FileAttributes && $item->fileSize() < $sizeInBytes;
        });
        return $this;
    }

    /**
     * Filter files with a size less than or equal to the specified value.
     *
     * @param int|string $size Maximum size (inclusive).
     * @return FilterBuilder|SizeFiltersTrait
     */
    public function sizeLte(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            return $item instanceof FileAttributes && $item->fileSize() <= $sizeInBytes;
        });
        return $this;
    }

    /**
     * Filter files within a specific size range (inclusive).
     *
     * @param int|string $min Minimum size (e.g., `'500K'`).
     * @param int|string $max Maximum size (e.g., `'5M'`).
     * @return FilterBuilder|SizeFiltersTrait
     */
    public function sizeBetween(int|string $min, int|string $max): self
    {
        $minBytes = $this->parseSize($min);
        $maxBytes = $this->parseSize($max);
        $this->addCondition(function (StorageAttributes $item) use ($minBytes, $maxBytes) {
            return $item instanceof FileAttributes &&
                $item->fileSize() >= $minBytes &&
                $item->fileSize() <= $maxBytes;
        });
        return $this;
    }
}
