<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\IsKnown;
use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the file extension (`pathinfo($path, PATHINFO_EXTENSION)`), case-insensitive by default.
 * Directories and files without an extension have no extension: they never match the
 * positive filters and always match the negative ones.
 *
 * @internal used by {@see FilterBuilder}
 */
trait ExtensionFiltersTrait
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
     * Extension is one of the values. Case-insensitive by default; a leading dot is ignored.
     *
     * @param string|list<string> $extensions e.g. `'jpg'`, `['txt', '.log']`
     */
    public function extensionEquals(string|array $extensions, bool $ignoreCase = true): static
    {
        return $this->addStringCondition(Subject::Extension, StringOperator::Equals, self::normalizeExtensions($extensions), $ignoreCase);
    }

    /**
     * Extension is none of the values (items without an extension, and directories, match).
     *
     * @param string|list<string> $extensions
     */
    public function extensionNotEquals(string|array $extensions, bool $ignoreCase = true): static
    {
        return $this->addStringCondition(Subject::Extension, StringOperator::Equals, self::normalizeExtensions($extensions), $ignoreCase, negate: true);
    }

    /**
     * Extension contains one of the substrings, e.g. `'ml'` matches `html`, `xml`.
     *
     * @param string|list<string> $substrings
     */
    public function extensionContains(string|array $substrings, bool $ignoreCase = true): static
    {
        return $this->addStringCondition(Subject::Extension, StringOperator::Contains, $substrings, $ignoreCase);
    }

    /**
     * Extension contains none of the substrings.
     *
     * @param string|list<string> $substrings
     */
    public function extensionNotContains(string|array $substrings, bool $ignoreCase = true): static
    {
        return $this->addStringCondition(Subject::Extension, StringOperator::Contains, $substrings, $ignoreCase, negate: true);
    }

    /**
     * Item has an extension.
     */
    public function hasExtension(): static
    {
        return $this->push(new IsKnown(Subject::Extension));
    }

    /**
     * @param string|list<string> $extensions
     *
     * @return list<string>
     */
    private static function normalizeExtensions(string|array $extensions): array
    {
        return array_map(
            static fn(string $extension): string => ltrim($extension, '.'),
            \is_array($extensions) ? $extensions : [$extensions],
        );
    }
}
