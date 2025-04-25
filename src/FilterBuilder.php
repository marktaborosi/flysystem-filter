<?php

namespace Marktaborosi\FlysystemFilter;

use Carbon\Carbon;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use LogicException;

class FilterBuilder
{
    private array $conditions = []; // Holds all conditions and logical expressions
    private array $currentGroup = []; // Tracks the current group of conditions
    private bool $expectingLogicalOperator = false; // Tracks if a logical operator is expected
    private int $groupLevel = 0; // Tracks the depth of grouping

    /**
     * Add a condition for filtering only files.
     *
     * @return $this
     */
    public function isFile(): self
    {
        $this->addCondition(fn(StorageAttributes $item) => $item instanceof FileAttributes);
        return $this;
    }

    /**
     * Add a condition for filtering only directories.
     *
     * @return $this
     */
    public function isDirectory(): self
    {
        $this->addCondition(fn(StorageAttributes $item) => $item instanceof DirectoryAttributes);
        return $this;
    }

    /**
     * Add a condition for exact path match.
     *
     * @param string|array $paths
     * @return $this
     */
    public function pathEquals(string|array $paths): self
    {
        $paths = (array)$paths;
        $this->addCondition(fn(StorageAttributes $item) => in_array($item->path(), $paths));
        return $this;
    }

    /**
     * Add a condition to filter by path matching a regex pattern.
     *
     * @param string $pattern
     * @return $this
     */
    public function pathMatchesRegex(string $pattern): self
    {
        $this->addCondition(fn(StorageAttributes $item) => preg_match($pattern, $item->path()));
        return $this;
    }


    /**
     * Add a condition for path containing a substring.
     *
     * @param string|array $substrings
     * @return $this
     */
    public function pathContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry || str_contains($item->path(), $substring), false));
        return $this;
    }

    /**
     * Add a condition for path not equal to a value.
     *
     * @param string|array $paths
     * @return $this
     */
    public function pathNotEquals(string|array $paths): self
    {
        $paths = (array)$paths;
        $this->addCondition(fn(StorageAttributes $item) => !in_array($item->path(), $paths));
        return $this;
    }

    /**
     * Add a condition for path not containing a substring.
     *
     * @param string|array $substrings
     * @return $this
     */
    public function pathNotContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry && !str_contains($item->path(), $substring), true));
        return $this;
    }

    /**
     * Add a condition for exact filename match (without extension).
     *
     * @param string|array $filenames
     * @return $this
     */
    public function filenameEquals(string|array $filenames): self
    {
        $filenames = (array)$filenames;
        $this->addCondition(fn(StorageAttributes $item) => in_array(pathinfo($item->path(), PATHINFO_FILENAME), $filenames));
        return $this;
    }

    /**
     * Add a condition for filename not equal to a value (without extension).
     *
     * @param string|array $filenames
     * @return $this
     */
    public function filenameNotEquals(string|array $filenames): self
    {
        $filenames = (array)$filenames;
        $this->addCondition(fn(StorageAttributes $item) => !in_array(pathinfo($item->path(), PATHINFO_FILENAME), $filenames));
        return $this;
    }

    /**
     * Add a condition for filename containing a substring (without extension).
     *
     * @param string|array $substrings
     * @return $this
     */
    public function filenameContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry || str_contains(pathinfo($item->path(), PATHINFO_FILENAME), $substring), false));
        return $this;
    }

    /**
     * Add a condition for filename not containing a substring (without extension).
     *
     * @param string|array $substrings
     * @return $this
     */
    public function filenameNotContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry && !str_contains(pathinfo($item->path(), PATHINFO_FILENAME), $substring), true));
        return $this;
    }

    /**
     * Add a condition to filter by filename matching a regex pattern.
     *
     * @param string $pattern
     * @return $this
     */
    public function filenameMatchesRegex(string $pattern): self
    {
        $this->addCondition(fn(StorageAttributes $item) => preg_match($pattern, pathinfo($item->path(), PATHINFO_FILENAME)));
        return $this;
    }


    /**
     * Add a condition for exact basename match (with extension).
     *
     * @param string|array $basename
     * @return $this
     */
    public function basenameEquals(string|array $basename): self
    {
        $basename = (array)$basename;
        $this->addCondition(fn(StorageAttributes $item) => in_array(basename($item->path()), $basename));
        return $this;
    }

    /**
     * Add a condition for basename not equal to a value (with extension).
     *
     * @param string|array $basename
     * @return $this
     */
    public function basenameNotEquals(string|array $basename): self
    {
        $basename = (array)$basename;
        $this->addCondition(fn(StorageAttributes $item) => !in_array(basename($item->path()), $basename));
        return $this;
    }

    /**
     * Add a condition for basename containing a substring (with extension).
     *
     * @param string|array $substrings
     * @return $this
     */
    public function basenameContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry || str_contains(basename($item->path()), $substring), false));
        return $this;
    }

    /**
     * Add a condition for basename not containing a substring (with extension).
     *
     * @param string|array $substrings
     * @return $this
     */
    public function basenameNotContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry && !str_contains(basename($item->path()), $substring), true));
        return $this;
    }


    /**
     * Add a condition for exact extension match.
     *
     * @param string|array $extensions
     * @return $this
     */
    public function extensionEquals(string|array $extensions): self
    {
        $extensions = array_map('strtolower', (array)$extensions);
        $this->addCondition(fn(StorageAttributes $item) => in_array(strtolower($this->getExtension($item->path())), $extensions, true));
        return $this;
    }

    /**
     * Add a condition for extension not equal to a value.
     *
     * @param string|array $extensions
     * @return $this
     */
    public function extensionNotEquals(string|array $extensions): self
    {
        $extensions = array_map('strtolower', (array)$extensions);
        $this->addCondition(fn(StorageAttributes $item) => !in_array(strtolower($this->getExtension($item->path())), $extensions, true));
        return $this;
    }

    /**
     * Add a condition for extension containing a substring.
     *
     * @param string|array $substrings
     * @return $this
     */
    public function extensionContains(string|array $substrings): self
    {
        $substrings = array_map('strtolower', (array)$substrings);
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry || str_contains(strtolower($this->getExtension($item->path()) ?? ''), $substring), false));
        return $this;
    }

    /**
     * Add a condition for extension not containing a substring.
     *
     * @param string|array $substrings
     * @return $this
     */
    public function extensionNotContains(string|array $substrings): self
    {
        $substrings = array_map('strtolower', (array)$substrings);
        $this->addCondition(fn(StorageAttributes $item) => array_reduce($substrings, fn($carry, $substring) => $carry && !str_contains(strtolower($this->getExtension($item->path()) ?? ''), $substring), true));
        return $this;
    }

    /**
     * Add a condition to filter by MIME type(s) determined by the extension.
     *
     * @param string|array $mimeTypes
     * @return $this
     */


    /**
     * Add a condition for file size equals.
     *
     * @param int|string $size
     * @return $this
     */
    public function sizeEquals(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            if (!$item instanceof FileAttributes || $item->fileSize() === null) {
                return false;
            }
            return $item->fileSize() === $sizeInBytes;
        });
        return $this;
    }

    /**
     * Add a condition for file size not equals.
     *
     * @param int|string $size
     * @return $this
     */
    public function sizeNotEquals(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            if (!$item instanceof FileAttributes || $item->fileSize() === null) {
                return false;
            }
            return $item->fileSize() !== $sizeInBytes;
        });
        return $this;
    }

    /**
     * Add a condition for file size greater than.
     *
     * @param int|string $size
     * @return $this
     */
    public function sizeGt(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            if (!$item instanceof FileAttributes || $item->fileSize() === null) {
                return false;
            }
            return $item->fileSize() > $sizeInBytes;
        });
        return $this;
    }


    /**
     * Add a condition for file size greater than or equals.
     *
     * @param int|string $size
     * @return $this
     */
    public function sizeGte(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            if (!$item instanceof FileAttributes || $item->fileSize() === null) {
                return false;
            }
            return $item->fileSize() >= $sizeInBytes;
        });
        return $this;
    }


    /**
     * Add a condition for file size less than.
     *
     * @param int|string $size
     * @return $this
     */
    public function sizeLt(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            if (!$item instanceof FileAttributes || $item->fileSize() === null) {
                return false;
            }
            return $item->fileSize() < $sizeInBytes;
        });
        return $this;
    }


    /**
     * Add a condition for file size less than or equals.
     *
     * @param int|string $size
     * @return $this
     */
    public function sizeLte(int|string $size): self
    {
        $sizeInBytes = $this->parseSize($size);
        $this->addCondition(function (StorageAttributes $item) use ($sizeInBytes) {
            if (!$item instanceof FileAttributes || $item->fileSize() === null) {
                return false;
            }
            return $item->fileSize() <= $sizeInBytes;
        });
        return $this;
    }

    /**
     * Add a condition to filter by size range.
     *
     * @param int|string $min
     * @param int|string $max
     * @return $this
     */
    public function sizeBetween(int|string $min, int|string $max): self
    {
        $minBytes = $this->parseSize($min);
        $maxBytes = $this->parseSize($max);
        $this->addCondition(function (StorageAttributes $item) use ($minBytes, $maxBytes) {
            if (!$item instanceof FileAttributes || $item->fileSize() === null) {
                return false;
            }
            return $item->fileSize() >= $minBytes && $item->fileSize() <= $maxBytes;
        });
        return $this;
    }

    /**
     * Add a condition to check if a metadata key exists.
     *
     * @param string $key
     * @return $this
     */
    public function hasMetadataKey(string $key): self
    {
        $this->addCondition(fn(StorageAttributes $item) => array_key_exists($key, $item->extraMetadata()));
        return $this;
    }

    /**
     * Add a condition to filter by metadata value equals.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function metadataEquals(string $key, mixed $value): self
    {
        $this->addCondition(fn(StorageAttributes $item) => isset($item->extraMetadata()[$key]) && $item->extraMetadata()[$key] === $value);
        return $this;
    }

    /**
     * Add a condition to filter by metadata value contains.
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function metadataContains(string $key, string $value): self
    {
        $this->addCondition(fn(StorageAttributes $item) => isset($item->extraMetadata()[$key]) && str_contains($item->extraMetadata()[$key], $value));
        return $this;
    }

    /**
     * Add a condition for items last modified before a timestamp.
     *
     * @param int|Carbon $timestamp
     * @return $this
     */
    public function lastModifiedBefore(int|Carbon $timestamp): self
    {
        $timestampValue = $timestamp instanceof Carbon ? $timestamp->timestamp : $timestamp;
        $this->addCondition(fn(StorageAttributes $item) => $item->lastModified() !== null && $item->lastModified() < $timestampValue);
        return $this;
    }

    /**
     * Add a condition for items last modified after a timestamp.
     *
     * @param int|Carbon $timestamp
     * @return $this
     */
    public function lastModifiedAfter(int|Carbon $timestamp): self
    {
        $timestampValue = $timestamp instanceof Carbon ? $timestamp->timestamp : $timestamp;
        $this->addCondition(fn(StorageAttributes $item) => $item->lastModified() !== null && $item->lastModified() > $timestampValue);
        return $this;
    }

    /**
     * Add a condition for items last modified between two timestamps.
     *
     * @param int|Carbon $start
     * @param int|Carbon $end
     * @return $this
     */
    public function lastModifiedBetween(int|Carbon $start, int|Carbon $end): self
    {
        $startTimestamp = $start instanceof Carbon ? $start->timestamp : $start;
        $endTimestamp = $end instanceof Carbon ? $end->timestamp : $end;
        $this->addCondition(fn(StorageAttributes $item) => $item->lastModified() !== null && $item->lastModified() >= $startTimestamp && $item->lastModified() <= $endTimestamp);
        return $this;
    }


    /**
     * Add a condition to filter for public visibility.
     *
     * @return $this
     */
    public function isPublic(): self
    {
        $this->addCondition(fn(StorageAttributes $item) => $item->visibility() === 'public');
        return $this;
    }

    /**
     * Add a condition to filter for private visibility.
     *
     * @return $this
     */
    public function isPrivate(): self
    {
        $this->addCondition(fn(StorageAttributes $item) => $item->visibility() === 'private');
        return $this;
    }


    /**
     * Logical AND.
     *
     * @return $this
     */
    public function and(): self
    {
        $this->currentGroup[] = 'AND';
        $this->expectingLogicalOperator = false;
        return $this;
    }

    /**
     * Logical OR.
     *
     * @return $this
     */
    public function or(): self
    {
        $this->currentGroup[] = 'OR';
        $this->expectingLogicalOperator = false;
        return $this;
    }

    /**
     * Start a group of conditions.
     *
     * @return $this
     */
    public function group_start(): self
    {
        if ($this->expectingLogicalOperator) {
            $this->currentGroup[] = 'AND';
        }

        $this->currentGroup[] = '(';
        $this->expectingLogicalOperator = false;
        $this->groupLevel++;
        return $this;
    }

    /**
     * End a group of conditions.
     *
     * @return $this
     */
    public function group_end(): self
    {
        if ($this->groupLevel <= 0) {
            throw new LogicException("Mismatched group_end: no corresponding group_start.");
        }

        $this->currentGroup[] = ')';
        $this->expectingLogicalOperator = true;
        $this->groupLevel--;
        return $this;
    }

    /**
     * Evaluate if an item matches all conditions.
     *
     * @param StorageAttributes $item
     * @return bool
     */
    public function matches(StorageAttributes $item): bool
    {
        // If no conditions have been added, return true
        if (empty($this->currentGroup)) {
            return true;
        }

        if ($this->groupLevel !== 0) {
            throw new LogicException("Unbalanced groups: missing group_end().");
        }

        $stack = [];
        foreach ($this->currentGroup as $element) {
            if ($element === '(' || $element === ')') {
                $stack[] = $element;
            } elseif ($element === 'AND') {
                $stack[] = '&&';
            } elseif ($element === 'OR') {
                $stack[] = '||';
            } elseif (is_callable($element)) {
                $stack[] = $element($item) ? 'true' : 'false';
            }
        }

        $expression = implode(' ', $stack);
        return eval("return $expression;");
    }


    /**
     * Add a condition to the current group.
     *
     * @param callable $condition
     */
    private function addCondition(callable $condition): void
    {
        if ($this->expectingLogicalOperator) {
            $this->currentGroup[] = 'AND';
        }

        $this->currentGroup[] = $condition;
        $this->expectingLogicalOperator = true;
    }

    /**
     * Validate and convert size to bytes.
     *
     * @param int|string $size
     * @return int
     * @throws LogicException If the size format is invalid.
     */
    private function parseSize(int|string $size): int
    {
        if (is_int($size)) {
            return $size;
        }

        if (preg_match('/^(\d+)([BKMGT])$/i', $size, $matches)) {
            $value = (int)$matches[1];
            $unit = strtoupper($matches[2]);

            return match ($unit) {
                'B' => $value,                      // Byte
                'K' => $value * 1024,               // Kilobyte
                'M' => $value * 1024 ** 2,          // Megabyte
                'G' => $value * 1024 ** 3,          // Gigabyte
                'T' => $value * 1024 ** 4,          // Terabyte
                default => throw new LogicException("Invalid unit: $unit"),
            };
        }

        throw new LogicException("Invalid size value: $size");
    }

    /**
     * Get the extension of a file from its path.
     *
     * @param string $path
     * @return string|null
     */
    private function getExtension(string $path): ?string
    {
        $info = pathinfo($path);
        return $info['extension'] ?? null;
    }

}
