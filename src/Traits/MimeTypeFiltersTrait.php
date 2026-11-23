<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the MIME type of files. The MIME type reported by the adapter is used when
 * available, otherwise it is detected from the extension (no file contents are read).
 * Directories have no MIME type. Comparisons are case-insensitive.
 *
 * @internal used by {@see FilterBuilder}
 */
trait MimeTypeFiltersTrait
{
    /**
     * @param string|int|list<string|int> $values
     *
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function addStringCondition(
        Subject $subject,
        StringOperator $operator,
        string|int|array $values,
        bool $ignoreCase = false,
        bool $negate = false,
    ): static;


    /**
     * MIME type is one of the values, e.g. `'image/png'`, `['application/pdf', 'text/plain']`.
     *
     * @param string|list<string> $mimeTypes
     */
    public function mimeTypeEquals(string|array $mimeTypes): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::Equals, $mimeTypes, ignoreCase: true);
    }

    /**
     * MIME type is none of the values (items with an unknown MIME type match).
     *
     * @param string|list<string> $mimeTypes
     */
    public function mimeTypeNotEquals(string|array $mimeTypes): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::Equals, $mimeTypes, ignoreCase: true, negate: true);
    }

    /**
     * MIME type contains one of the substrings, e.g. `'image'`, `['zip', 'tar']`.
     *
     * @param string|list<string> $substrings
     */
    public function mimeTypeContains(string|array $substrings): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::Contains, $substrings, ignoreCase: true);
    }

    /**
     * MIME type contains none of the substrings (items with an unknown MIME type match).
     *
     * @param string|list<string> $substrings
     */
    public function mimeTypeNotContains(string|array $substrings): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::Contains, $substrings, ignoreCase: true, negate: true);
    }

    /**
     * MIME type matches a glob, e.g. `'image/*'`, `'application/{zip,gzip,x-tar}'`.
     *
     * @param string|list<string> $patterns
     */
    public function mimeTypeMatches(string|array $patterns): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::Glob, $patterns, ignoreCase: true);
    }

    /**
     * Image files (`image/*`).
     */
    public function isImage(): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::StartsWith, 'image/', ignoreCase: true);
    }

    /**
     * Video files (`video/*`).
     */
    public function isVideo(): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::StartsWith, 'video/', ignoreCase: true);
    }

    /**
     * Audio files (`audio/*`).
     */
    public function isAudio(): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::StartsWith, 'audio/', ignoreCase: true);
    }

    /**
     * Text files (`text/*`).
     */
    public function isText(): static
    {
        return $this->addStringCondition(Subject::MimeType, StringOperator::StartsWith, 'text/', ignoreCase: true);
    }
}
