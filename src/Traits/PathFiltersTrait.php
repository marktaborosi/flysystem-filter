<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait PathFiltersTrait
 *
 * Provides filtering methods based on the full path of a Flysystem item.
 *
 * These filters operate on the result of `$item->path()`, allowing you to match
 * full paths, partial segments, prefixes, suffixes, and regular expressions.
 *
 * Designed for use within {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait PathFiltersTrait
{
    /**
     * Filter items whose path exactly matches one or more given values.
     *
     * @param string|array $paths Absolute or relative paths to match (e.g. 'logs/error.log').
     * @return FilterBuilder|PathFiltersTrait
     */
    public function pathEquals(string|array $paths): self
    {
        $paths = (array)$paths;
        $this->addCondition(fn(StorageAttributes $item) =>
        in_array($item->path(), $paths)
        );
        return $this;
    }

    /**
     * Filter items whose path does not match any of the specified values.
     *
     * @param string|array $paths One or more paths to exclude.
     * @return FilterBuilder|PathFiltersTrait
     */
    public function pathNotEquals(string|array $paths): self
    {
        $paths = (array)$paths;
        $this->addCondition(fn(StorageAttributes $item) =>
        !in_array($item->path(), $paths)
        );
        return $this;
    }

    /**
     * Filter items whose path contains at least one of the provided substrings.
     *
     * @param string|array $substrings One or more substrings to match (e.g. 'backup', 'archive').
     * @return FilterBuilder|PathFiltersTrait
     */
    public function pathContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) =>
        array_reduce(
            $substrings,
            fn($carry, $substring) =>
                $carry || str_contains($item->path(), $substring),
            false
        )
        );
        return $this;
    }

    /**
     * Filter items whose path does not contain any of the specified substrings.
     *
     * @param string|array $substrings One or more substrings that must not appear in the path.
     * @return FilterBuilder|PathFiltersTrait
     */
    public function pathNotContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;
        $this->addCondition(fn(StorageAttributes $item) =>
        array_reduce(
            $substrings,
            fn($carry, $substring) =>
                $carry && !str_contains($item->path(), $substring),
            true
        )
        );
        return $this;
    }

    /**
     * Filter items whose path matches a given regular expression.
     *
     * Useful for advanced pattern-based filtering.
     *
     * @param string $pattern A valid PCRE regex (e.g. '/\/logs\/\d{4}\/.*\.log$/').
     * @return FilterBuilder|PathFiltersTrait
     */
    public function pathMatchesRegex(string $pattern): self
    {
        $this->addCondition(fn(StorageAttributes $item) =>
        preg_match($pattern, $item->path())
        );
        return $this;
    }

    /**
     * Filter items whose path starts with one of the given prefixes.
     *
     * Useful for filtering by virtual directories or key namespaces.
     *
     * @param string|array $prefixes One or more prefix strings (e.g. 'tmp/', 'uploads/').
     * @return FilterBuilder|PathFiltersTrait
     */
    public function pathStartsWith(string|array $prefixes): self
    {
        $prefixes = (array)$prefixes;
        $this->addCondition(function (StorageAttributes $item) use ($prefixes) {
            $path = $item->path();
            foreach ($prefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            }
            return false;
        });
        return $this;
    }

    /**
     * Filter items whose path ends with one of the given suffixes.
     *
     * @param string|array $suffixes One or more suffixes (e.g. '.log', '_backup.json').
     * @return FilterBuilder|PathFiltersTrait
     */
    public function pathEndsWith(string|array $suffixes): self
    {
        $suffixes = (array)$suffixes;
        $this->addCondition(function (StorageAttributes $item) use ($suffixes) {
            $path = $item->path();
            foreach ($suffixes as $suffix) {
                if (str_ends_with($path, $suffix)) {
                    return true;
                }
            }
            return false;
        });
        return $this;
    }
}
