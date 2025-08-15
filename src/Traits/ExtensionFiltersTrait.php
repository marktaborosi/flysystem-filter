<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait ExtensionFiltersTrait
 *
 * Provides filtering methods based on file extensions.
 * These filters operate on the result of `pathinfo($item->path(), PATHINFO_EXTENSION)`,
 * and are case-insensitive by default.
 *
 * Designed to be used within {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait ExtensionFiltersTrait
{
    /**
     * Filter files whose extension exactly matches one or more given extensions.
     *
     * Matching is case-insensitive.
     *
     * @param string|array $extensions e.g. 'jpg', ['txt', 'log']
     * @return ExtensionFiltersTrait|FilterBuilder
     */
    public function extensionEquals(string|array $extensions): self
    {
        $extensions = array_map('strtolower', (array)$extensions);
        $this->addCondition(fn(StorageAttributes $item) =>
        in_array(strtolower($this->getExtension($item->path())), $extensions, true)
        );
        return $this;
    }

    /**
     * Filter files whose extension does not match any of the specified values.
     *
     * Matching is case-insensitive.
     *
     * @param string|array $extensions e.g. 'csv', ['exe', 'bak']
     * @return ExtensionFiltersTrait|FilterBuilder
     */
    public function extensionNotEquals(string|array $extensions): self
    {
        $extensions = array_map('strtolower', (array)$extensions);
        $this->addCondition(fn(StorageAttributes $item) =>
        !in_array(strtolower($this->getExtension($item->path())), $extensions, true)
        );
        return $this;
    }

    /**
     * Filter files whose extension contains one or more substrings.
     *
     * This is useful for flexible pattern matches like:
     * - extensions that include "ml" (`html`, `xml`, `mhtml`)
     *
     * @param string|array $substrings
     * @return ExtensionFiltersTrait|FilterBuilder
     */
    public function extensionContains(string|array $substrings): self
    {
        $substrings = array_map('strtolower', (array)$substrings);
        $this->addCondition(fn(StorageAttributes $item) =>
        array_reduce($substrings, fn($carry, $substring) =>
            $carry || str_contains(strtolower($this->getExtension($item->path()) ?? ''), $substring),
            false
        )
        );
        return $this;
    }

    /**
     * Filter files whose extension does not contain any of the specified substrings.
     *
     * @param string|array $substrings
     * @return ExtensionFiltersTrait|FilterBuilder
     */
    public function extensionNotContains(string|array $substrings): self
    {
        $substrings = array_map('strtolower', (array)$substrings);
        $this->addCondition(fn(StorageAttributes $item) =>
        array_reduce($substrings, fn($carry, $substring) =>
            $carry && !str_contains(strtolower($this->getExtension($item->path()) ?? ''), $substring),
            true
        )
        );
        return $this;
    }
}
