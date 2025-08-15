<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait DepthFiltersTrait
 *
 * Provides filtering capabilities based on the depth (hierarchical level) of a path.
 *
 * Depth is determined by the number of slashes (`/`) in the normalized path string.
 * For example:
 * - `'file.txt'` → depth `0`
 * - `'documents/file.txt'` → depth `1`
 * - `'a/b/c/file.txt'` → depth `3`
 *
 * This trait is designed for use within {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait DepthFiltersTrait
{
    /**
     * Filter items whose path has an exact depth.
     *
     * @param int $depth The exact depth to match (0 = top-level file or folder).
     * @return DepthFiltersTrait|FilterBuilder
     */
    public function depthEquals(int $depth): self
    {
        $this->addCondition(function (StorageAttributes $item) use ($depth) {
            return $this->calculateDepth($item->path()) === $depth;
        });

        return $this;
    }

    /**
     * Filter items whose path is deeper than the specified depth.
     *
     * @param int $depth The minimum depth (exclusive).
     * @return DepthFiltersTrait|FilterBuilder
     */
    public function depthGt(int $depth): self
    {
        $this->addCondition(function (StorageAttributes $item) use ($depth) {
            return $this->calculateDepth($item->path()) > $depth;
        });

        return $this;
    }

    /**
     * Filter items whose path is shallower than the specified depth.
     *
     * @param int $depth The maximum depth (exclusive).
     * @return DepthFiltersTrait|FilterBuilder
     */
    public function depthLt(int $depth): self
    {
        $this->addCondition(function (StorageAttributes $item) use ($depth) {
            return $this->calculateDepth($item->path()) < $depth;
        });

        return $this;
    }
}
