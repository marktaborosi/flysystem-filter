<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait FileTypeFiltersTrait
 *
 * Provides methods to filter Flysystem items by type: file or directory.
 * This is based on instance checks against FileAttributes or DirectoryAttributes.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait FileTypeFiltersTrait
{
    /**
     * Filter only items that are files.
     *
     * @return FilterBuilder|FileTypeFiltersTrait
     */
    public function isFile(): self
    {
        $this->addCondition(fn(StorageAttributes $item) => $item instanceof FileAttributes);
        return $this;
    }

    /**
     * Filter only items that are directories.
     *
     * @return FilterBuilder|FileTypeFiltersTrait
     */
    public function isDirectory(): self
    {
        $this->addCondition(fn(StorageAttributes $item) => $item instanceof DirectoryAttributes);
        return $this;
    }
}
