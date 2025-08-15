<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait VisibilityFiltersTrait
 *
 * Provides filtering methods based on the visibility metadata of Flysystem items.
 * Visibility typically reflects access control, such as `'public'`, `'private'`,
 * or other custom visibility schemes depending on the adapter.
 *
 * This trait is intended for use within {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait VisibilityFiltersTrait
{
    /**
     * Filter items that are explicitly marked as publicly visible.
     *
     * Equivalent to checking: `$item->visibility() === 'public'`.
     *
     * @return FilterBuilder|VisibilityFiltersTrait
     */
    public function isPublic(): self
    {
        $this->addCondition(fn(StorageAttributes $item) =>
            $item->visibility() === 'public'
        );
        return $this;
    }

    /**
     * Filter items that are explicitly marked as private.
     *
     * Equivalent to checking: `$item->visibility() === 'private'`.
     *
     * @return FilterBuilder|VisibilityFiltersTrait
     */
    public function isPrivate(): self
    {
        $this->addCondition(fn(StorageAttributes $item) =>
            $item->visibility() === 'private'
        );
        return $this;
    }

    /**
     * Filter items whose visibility exactly matches one or more specified values.
     *
     * Useful for supporting custom visibility settings (e.g., `'protected'`, `'internal'`).
     *
     * Example:
     * ```php
     * $filter->visibilityEquals(['team', 'admin']);
     * ```
     *
     * @param string|array $visibilities One or more visibility states to match.
     * @return FilterBuilder|VisibilityFiltersTrait
     */
    public function visibilityEquals(string|array $visibilities): self
    {
        $visibilities = (array)$visibilities;

        $this->addCondition(function (StorageAttributes $item) use ($visibilities) {
            return in_array($item->visibility(), $visibilities, true);
        });

        return $this;
    }

    /**
     * Filter items whose visibility contains one or more substrings.
     *
     * Supports partial matching against visibility states. This is especially useful
     * if visibility is customized (e.g., `'user-public'`, `'readonly-link'`, etc.).
     *
     * Example:
     * ```php
     * $filter->visibilityContains('public');
     * ```
     *
     * @param string|array $substrings One or more substrings to search for.
     * @return FilterBuilder|VisibilityFiltersTrait
     */
    public function visibilityContains(string|array $substrings): self
    {
        $substrings = (array)$substrings;

        $this->addCondition(function (StorageAttributes $item) use ($substrings) {
            $visibility = $item->visibility() ?? '';

            foreach ($substrings as $substring) {
                if (str_contains($visibility, $substring)) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }
}
