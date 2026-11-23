<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the basename: the last path segment **with** its extension (`basename($path)`),
 * e.g. `file.txt` for `docs/file.txt`.
 *
 * @internal used by {@see FilterBuilder}
 */
trait BasenameFiltersTrait
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
     * Basename (filename with extension) is exactly one of the values.
     *
     * @param string|list<string> $basenames e.g. `['file.txt', 'report.csv']`
     */
    public function basenameEquals(string|array $basenames, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::Equals, $basenames, $ignoreCase);
    }

    /**
     * Basename is none of the values.
     *
     * @param string|list<string> $basenames
     */
    public function basenameNotEquals(string|array $basenames, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::Equals, $basenames, $ignoreCase, negate: true);
    }

    /**
     * Basename contains at least one of the substrings.
     *
     * @param string|list<string> $substrings
     */
    public function basenameContains(string|array $substrings, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::Contains, $substrings, $ignoreCase);
    }

    /**
     * Basename contains none of the substrings.
     *
     * @param string|list<string> $substrings
     */
    public function basenameNotContains(string|array $substrings, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::Contains, $substrings, $ignoreCase, negate: true);
    }

    /**
     * Basename starts with one of the prefixes.
     *
     * @param string|list<string> $prefixes e.g. `'log_'`
     */
    public function basenameStartsWith(string|array $prefixes, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::StartsWith, $prefixes, $ignoreCase);
    }

    /**
     * Basename ends with one of the suffixes.
     *
     * @param string|list<string> $suffixes e.g. `'.tar.gz'`
     */
    public function basenameEndsWith(string|array $suffixes, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::EndsWith, $suffixes, $ignoreCase);
    }

    /**
     * Basename matches a PCRE pattern (with delimiters), validated immediately.
     *
     * @param string|list<string> $patterns
     *
     * @throws InvalidArgumentException for an invalid pattern
     */
    public function basenameMatchesRegex(string|array $patterns): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::Regex, $patterns);
    }

    /**
     * Basename matches a glob pattern (`*`, `?`, `[abc]`, `{a,b}`).
     *
     * @param string|list<string> $patterns e.g. `'*.{jpg,png}'`
     *
     * @throws InvalidArgumentException for an invalid pattern
     */
    public function basenameMatchesGlob(string|array $patterns, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Basename, StringOperator::Glob, $patterns, $ignoreCase);
    }
}
