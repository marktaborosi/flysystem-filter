<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\AnyOf;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\StringCondition;
use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the full path of an item (`$item->path()`), e.g. `logs/2025/app.log`.
 *
 * Every method accepts one value or a list of values; the condition matches when **any** value matches.
 *
 * @internal used by {@see FilterBuilder}
 */
trait PathFiltersTrait
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
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    /**
     * Path is exactly one of the given values.
     *
     * @param string|list<string> $paths e.g. `'logs/error.log'`
     */
    public function pathEquals(string|array $paths, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::Equals, $paths, $ignoreCase);
    }

    /**
     * Path is none of the given values.
     *
     * @param string|list<string> $paths
     */
    public function pathNotEquals(string|array $paths, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::Equals, $paths, $ignoreCase, negate: true);
    }

    /**
     * Path contains at least one of the substrings.
     *
     * @param string|list<string> $substrings e.g. `['backup', 'archive']`
     */
    public function pathContains(string|array $substrings, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::Contains, $substrings, $ignoreCase);
    }

    /**
     * Path contains none of the substrings.
     *
     * @param string|list<string> $substrings
     */
    public function pathNotContains(string|array $substrings, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::Contains, $substrings, $ignoreCase, negate: true);
    }

    /**
     * Path starts with one of the prefixes.
     *
     * @param string|list<string> $prefixes e.g. `'tmp/'`
     */
    public function pathStartsWith(string|array $prefixes, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::StartsWith, $prefixes, $ignoreCase);
    }

    /**
     * Path ends with one of the suffixes.
     *
     * @param string|list<string> $suffixes e.g. `'_backup.json'`
     */
    public function pathEndsWith(string|array $suffixes, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::EndsWith, $suffixes, $ignoreCase);
    }

    /**
     * Path matches a PCRE pattern (with delimiters), e.g. `'#^logs/\d{4}/.+\.log$#'`.
     * The pattern is validated immediately.
     *
     * @param string|list<string> $patterns
     *
     * @throws InvalidArgumentException for an invalid pattern
     */
    public function pathMatchesRegex(string|array $patterns): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::Regex, $patterns);
    }

    /**
     * Path matches a glob pattern: `*` (no `/`), `**` (any depth), `?`, `[abc]`, `{a,b}`.
     *
     * ```php
     * $filter->pathMatchesGlob('logs/**.{log,gz}');   // logs/app.log, logs/2025/01/app.gz
     * ```
     *
     * @param string|list<string> $patterns
     */
    public function pathMatchesGlob(string|array $patterns, bool $ignoreCase = false): static
    {
        return $this->addStringCondition(Subject::Path, StringOperator::Glob, $patterns, $ignoreCase);
    }

    /**
     * Item lives directly in one of the directories (`''` is the root).
     * With `$recursive = true` items in any sub-directory match as well.
     *
     * ```php
     * $filter->inDirectory('logs');                   // logs/app.log
     * $filter->inDirectory('logs', recursive: true);  // logs/app.log, logs/2025/01/app.log
     * ```
     *
     * @param string|list<string> $directories
     */
    public function inDirectory(string|array $directories, bool $recursive = false): static
    {
        $directories = array_map(
            static fn(string $directory): string => trim($directory, '/'),
            \is_array($directories) ? $directories : [$directories],
        );

        if (!$recursive) {
            return $this->addStringCondition(Subject::Dirname, StringOperator::Equals, $directories);
        }

        if (\in_array('', $directories, true)) {
            return $this->push(new AllOf());
        }

        return $this->push(new AnyOf(
            new StringCondition(Subject::Dirname, StringOperator::Equals, $directories),
            new StringCondition(Subject::Dirname, StringOperator::StartsWith, array_map(
                static fn(string $directory): string => $directory . '/',
                $directories,
            )),
        ));
    }
}
