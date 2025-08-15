<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait FilenameFiltersTrait
 *
 * Provides filtering methods that target the **filename** portion of a path (excluding the file extension).
 *
 * The filename is extracted using `pathinfo($item->path(), PATHINFO_FILENAME)`.
 * This is useful when you want to match the name without concern for its extension.
 *
 * For example, for the path `logs/system-error.log`, the filename is `system-error`.
 *
 * Intended for use within {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait FilenameFiltersTrait
{
    /**
     * Filter items where the filename matches one of the provided values exactly.
     *
     * @param string|array $filenames One or more filenames (e.g., 'invoice', 'report2024').
     * @return FilterBuilder|FilenameFiltersTrait
     */
    public function filenameEquals(string|array $filenames): self
    {
        $filenames = (array)$filenames;
        $this->addCondition(fn(StorageAttributes $item) =>
        in_array(pathinfo($item->path(), PATHINFO_FILENAME), $filenames)
        );
        return $this;
    }

    /**
     * Filter items where the filename does not match any of the provided values.
     *
     * @param string|array $filenames One or more filenames to exclude.
     * @return FilterBuilder|FilenameFiltersTrait
     */
    public function filenameNotEquals(string|array $filenames): self
    {
        $filenames = (array)$filenames;
        $this->addCondition(fn(StorageAttributes $item) =>
        !in_array(pathinfo($item->path(), PATHINFO_FILENAME), $filenames)
        );
        return $this;
    }

    /**
     * Filter items whose filename starts with one of the specified prefixes.
     *
     * @param string|array $prefixes One or more starting strings (e.g., 'report_', 'log_').
     * @return FilterBuilder|FilenameFiltersTrait
     */
    public function filenameStartsWith(string|array $prefixes): self
    {
        $prefixes = (array)$prefixes;

        $this->addCondition(function (StorageAttributes $item) use ($prefixes) {
            $filename = pathinfo($item->path(), PATHINFO_FILENAME);

            foreach ($prefixes as $prefix) {
                if (str_starts_with($filename, $prefix)) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    /**
     * Filter items whose filename ends with one of the specified suffixes.
     *
     * @param string|array $suffixes One or more ending strings (e.g., '_final', '_backup').
     * @return FilterBuilder|FilenameFiltersTrait
     */
    public function filenameEndsWith(string|array $suffixes): self
    {
        $suffixes = (array)$suffixes;

        $this->addCondition(function (StorageAttributes $item) use ($suffixes) {
            $filename = pathinfo($item->path(), PATHINFO_FILENAME);

            foreach ($suffixes as $suffix) {
                if (str_ends_with($filename, $suffix)) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    /**
     * Filter items whose filename contains one or more of the specified substrings.
     *
     * @param string|array $substrings Substrings to search for in the filename.
     * @return FilterBuilder|FilenameFiltersTrait
     */
    public function filenameContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) =>
        array_reduce(
            $substrings,
            fn($carry, $substring) =>
                $carry || str_contains(pathinfo($item->path(), PATHINFO_FILENAME), $substring),
            false
        )
        );
        return $this;
    }

    /**
     * Filter items whose filename does not contain any of the specified substrings.
     *
     * @param string|array $substrings Substrings to exclude from the filename.
     * @return FilterBuilder|FilenameFiltersTrait
     */
    public function filenameNotContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) =>
        array_reduce(
            $substrings,
            fn($carry, $substring) =>
                $carry && !str_contains(pathinfo($item->path(), PATHINFO_FILENAME), $substring),
            true
        )
        );
        return $this;
    }

    /**
     * Filter items whose filename matches a regular expression.
     *
     * @param string $pattern A valid PCRE regex pattern.
     * @return FilterBuilder|FilenameFiltersTrait
     */
    public function filenameMatchesRegex(string $pattern): self
    {
        $this->addCondition(fn(StorageAttributes $item) =>
        preg_match($pattern, pathinfo($item->path(), PATHINFO_FILENAME))
        );
        return $this;
    }
}
