<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the filename **without** its extension (`pathinfo($path, PATHINFO_FILENAME)`):
 * for `logs/system-error.log` the filename is `system-error`.
 *
 * @internal used by {@see FilterBuilder}
 */
trait FilenameFiltersTrait
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
     * Filename (without extension) is exactly one of the values.
     *
     * @param string|list<string> $filenames e.g. `['invoice', 'report-2025']`
     */
    public function filenameEquals(string|array $filenames, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::Equals, $filenames, $ignoreCase);
    }

    /**
     * Filename (without extension) is none of the values.
     *
     * @param string|list<string> $filenames
     */
    public function filenameNotEquals(string|array $filenames, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::Equals, $filenames, $ignoreCase, negate: true);
    }

    /**
     * Filename contains at least one of the substrings.
     *
     * @param string|list<string> $substrings
     */
    public function filenameContains(string|array $substrings, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::Contains, $substrings, $ignoreCase);
    }

    /**
     * Filename contains none of the substrings.
     *
     * @param string|list<string> $substrings
     */
    public function filenameNotContains(string|array $substrings, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::Contains, $substrings, $ignoreCase, negate: true);
    }

    /**
     * Filename starts with one of the prefixes.
     *
     * @param string|list<string> $prefixes e.g. `'report_'`
     */
    public function filenameStartsWith(string|array $prefixes, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::StartsWith, $prefixes, $ignoreCase);
    }

    /**
     * Filename ends with one of the suffixes.
     *
     * @param string|list<string> $suffixes e.g. `'_final'`
     */
    public function filenameEndsWith(string|array $suffixes, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::EndsWith, $suffixes, $ignoreCase);
    }

    /**
     * Filename matches a PCRE pattern (with delimiters), validated immediately.
     *
     * @param string|list<string> $patterns e.g. `'/^report-\d{4}$/'`
     *
     * @throws InvalidArgumentException for an invalid pattern
     */
    public function filenameMatchesRegex(string|array $patterns): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::Regex, $patterns);
    }

    /**
     * Filename matches a glob pattern (`*`, `?`, `[abc]`, `{a,b}`).
     *
     * @param string|list<string> $patterns e.g. `'report-20??'`
     *
     * @throws InvalidArgumentException for an invalid pattern
     */
    public function filenameMatchesGlob(string|array $patterns, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Filename, StringOperator::Glob, $patterns, $ignoreCase);
    }
}
