<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait BasenameFiltersTrait
 *
 * Provides filter methods for working with file and directory basenames.
 * The basename refers to the filename **with** its extension (e.g., `file.txt`).
 *
 * These filters operate on the result of `basename($item->path())`, which extracts
 * the last segment of a path — commonly used for filtering based on exact filenames.
 *
 * Designed to be used inside the {@see \Marktaborosi\FlysystemFilter\FilterBuilder} class.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait BasenameFiltersTrait
{
    /**
     * Filter items whose basename matches one of the specified values exactly.
     *
     * @param string|array $basename One or more basenames (e.g., ['file.txt', 'report.csv']).
     * @return FilterBuilder|BasenameFiltersTrait
     */
    public function basenameEquals(string|array $basename): self
    {
        $basename = (array)$basename;
        $this->addCondition(fn(StorageAttributes $item) => in_array(basename($item->path()), $basename));
        return $this;
    }

    /**
     * Filter items whose basename does not match any of the specified values.
     *
     * @param string|array $basename One or more basenames to exclude.
     * @return FilterBuilder|BasenameFiltersTrait
     */
    public function basenameNotEquals(string|array $basename): self
    {
        $basename = (array)$basename;
        $this->addCondition(fn(StorageAttributes $item) => !in_array(basename($item->path()), $basename));
        return $this;
    }

    /**
     * Filter items whose basename starts with one of the given prefixes.
     *
     * @param string|array $prefixes One or more starting substrings to match (e.g., 'report_', 'log_').
     * @return FilterBuilder|BasenameFiltersTrait
     */
    public function basenameStartsWith(string|array $prefixes): self
    {
        $prefixes = (array)$prefixes;

        $this->addCondition(function (StorageAttributes $item) use ($prefixes) {
            $basename = basename($item->path());

            foreach ($prefixes as $prefix) {
                if (str_starts_with($basename, $prefix)) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    /**
     * Filter items whose basename ends with one of the given suffixes.
     *
     * @param string|array $suffixes One or more ending substrings to match (e.g., '.txt', '_final.csv').
     * @return FilterBuilder|BasenameFiltersTrait
     */
    public function basenameEndsWith(string|array $suffixes): self
    {
        $suffixes = (array)$suffixes;

        $this->addCondition(function (StorageAttributes $item) use ($suffixes) {
            $basename = basename($item->path());

            foreach ($suffixes as $suffix) {
                if (str_ends_with($basename, $suffix)) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    /**
     * Filter items whose basename contains at least one of the given substrings.
     *
     * @param string|array $substrings One or more substrings to search within the basename.
     * @return FilterBuilder|BasenameFiltersTrait
     */
    public function basenameContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce(
            $substrings,
            fn($carry, $substring) => $carry || str_contains(basename($item->path()), $substring),
            false
        ));
        return $this;
    }

    /**
     * Filter items whose basename does not contain any of the specified substrings.
     *
     * @param string|array $substrings One or more substrings to ensure are absent from the basename.
     * @return FilterBuilder|BasenameFiltersTrait
     */
    public function basenameNotContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) => array_reduce(
            $substrings,
            fn($carry, $substring) => $carry && !str_contains(basename($item->path()), $substring),
            true
        ));
        return $this;
    }
}
