<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait MimeTypeFiltersTrait
 *
 * Provides filtering capabilities based on MIME types, as inferred from the file path
 * using `league/mime-type-detection`. No file contents are read; detection is purely
 * based on the file extension.
 *
 * MIME type filters are useful for restricting results to media types like images,
 * text files, archives, etc.
 *
 * Designed for use inside {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait MimeTypeFiltersTrait
{
    /**
     * Filter items whose MIME type matches one of the specified values exactly.
     *
     * Example:
     * ```php
     * $filter->mimeTypeEquals('image/png');
     * $filter->mimeTypeEquals(['application/pdf', 'text/plain']);
     * ```
     *
     * @param string|array $mimeTypes One or more MIME types (e.g. 'image/png', 'application/json').
     * @return FilterBuilder|MimeTypeFiltersTrait
     */
    public function mimeTypeEquals(string|array $mimeTypes): self
    {
        $mimeTypes = (array)$mimeTypes;

        $this->addCondition(function (StorageAttributes $item) use ($mimeTypes) {
            $mimeType = $this->getMimeTypeFromPath($item->path());
            return $mimeType !== null && in_array($mimeType, $mimeTypes, true);
        });

        return $this;
    }

    /**
     * Filter items whose MIME type does not match any of the specified values.
     *
     * Example:
     * ```php
     * $filter->mimeTypeNotEquals('application/zip');
     * ```
     *
     * @param string|array $mimeTypes One or more MIME types to exclude.
     * @return FilterBuilder|MimeTypeFiltersTrait
     */
    public function mimeTypeNotEquals(string|array $mimeTypes): self
    {
        $mimeTypes = (array)$mimeTypes;

        $this->addCondition(function (StorageAttributes $item) use ($mimeTypes) {
            $mimeType = $this->getMimeTypeFromPath($item->path());
            return $mimeType === null || !in_array($mimeType, $mimeTypes, true);
        });

        return $this;
    }

    /**
     * Filter items whose MIME type contains any of the provided substrings.
     *
     * Useful for broad category matches like:
     * - `'image'` to match `'image/png'`, `'image/jpeg'`, etc.
     * - `'application'` to match `'application/pdf'`, etc.
     *
     * Example:
     * ```php
     * $filter->mimeTypeContains(['image', 'text']);
     * ```
     *
     * @param string|array $substrings One or more substrings to include.
     * @return FilterBuilder|MimeTypeFiltersTrait
     */
    public function mimeTypeContains(string|array $substrings): self
    {
        return $this->filterMimeTypeContains((array)$substrings, false);
    }

    /**
     * Filter items whose MIME type does not contain any of the provided substrings.
     *
     * Example:
     * ```php
     * $filter->mimeTypeNotContains('video');
     * ```
     *
     * @param string|array $substrings One or more substrings to exclude.
     * @return FilterBuilder|MimeTypeFiltersTrait
     */
    public function mimeTypeNotContains(string|array $substrings): self
    {
        return $this->filterMimeTypeContains((array)$substrings, true);
    }

    /**
     * Internal helper to evaluate MIME type substrings with optional negation logic.
     *
     * @param array $substrings Substrings to test against the MIME type.
     * @param bool $negate If true, inverts the result (for "not contains" logic).
     * @return FilterBuilder|MimeTypeFiltersTrait
     */
    private function filterMimeTypeContains(array $substrings, bool $negate): self
    {
        $this->addCondition(function (StorageAttributes $item) use ($substrings, $negate) {
            $mimeType = $this->getMimeTypeFromPath($item->path());

            if ($mimeType === null) {
                return $negate;
            }

            foreach ($substrings as $substring) {
                if (str_contains($mimeType, $substring)) {
                    return !$negate;
                }
            }

            return $negate;
        });

        return $this;
    }
}
