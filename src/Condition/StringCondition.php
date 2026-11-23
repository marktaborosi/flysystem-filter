<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\MimeTypeDetector;
use Marktaborosi\FlysystemFilter\Support\Glob;
use Marktaborosi\FlysystemFilter\Support\MimeTypes;
use Marktaborosi\FlysystemFilter\Support\Regex;

/**
 * Compares a string property of an item ({@see Subject}) with one or more values.
 *
 * The condition matches when the subject matches **any** of the values. When the subject
 * is unknown (e.g. a file without an extension) the condition never matches; wrap it in
 * {@see Not} to express "does not equal / contain".
 *
 * Patterns (regex and glob) are validated when the condition is created.
 */
final readonly class StringCondition implements SerializableCondition
{
    /** @var list<string> */
    public array $values;

    /** @var list<string> Values prepared for comparison (lower-cased or compiled to regex). */
    private array $needles;

    private MimeTypeDetector $mimeTypeDetector;

    /**
     * @param string|int|list<string|int> $values
     */
    public function __construct(
        public Subject $subject,
        public StringOperator $operator,
        string|int|array $values,
        public bool $ignoreCase = false,
        ?MimeTypeDetector $mimeTypeDetector = null,
    ) {
        $this->values = array_values(array_map(
            static fn(string|int $value): string => (string) $value,
            \is_array($values) ? $values : [$values],
        ));
        $this->mimeTypeDetector = $mimeTypeDetector ?? MimeTypes::defaultDetector();
        $this->needles = array_map($this->prepare(...), $this->values);
    }

    public function matches(StorageAttributes $item): bool
    {
        $subject = $this->subject->resolve($item, $this->mimeTypeDetector);

        if ($subject === null) {
            return false;
        }

        if ($this->ignoreCase && !$this->operator->isPattern()) {
            $subject = self::lower($subject);
        }

        foreach ($this->needles as $needle) {
            $matched = match ($this->operator) {
                StringOperator::Equals => $subject === $needle,
                StringOperator::Contains => str_contains($subject, $needle),
                StringOperator::StartsWith => str_starts_with($subject, $needle),
                StringOperator::EndsWith => str_ends_with($subject, $needle),
                StringOperator::Regex, StringOperator::Glob => preg_match($needle, $subject) === 1,
            };

            if ($matched) {
                return true;
            }
        }

        return false;
    }

    public function describe(): string
    {
        $values = \count($this->values) === 1
            ? $this->values[0]
            : '[' . implode(', ', $this->values) . ']';

        $caseNote = '';
        if ($this->operator !== StringOperator::Regex && $this->ignoreCase !== $this->subject->isCaseInsensitiveByDefault()) {
            $caseNote = $this->ignoreCase ? ' (ignore case)' : ' (case-sensitive)';
        }

        return \sprintf('%s %s %s%s', $this->subject->label(), $this->operator->label(), $values, $caseNote);
    }

    public function toArray(): array
    {
        return [
            'type' => 'string',
            'subject' => $this->subject->value,
            'operator' => $this->operator->value,
            'values' => $this->values,
            'ignoreCase' => $this->ignoreCase,
        ];
    }

    private function prepare(string $value): string
    {
        return match ($this->operator) {
            StringOperator::Regex => self::validRegex($value),
            StringOperator::Glob => Glob::toRegex($value, $this->ignoreCase),
            default => $this->ignoreCase ? self::lower($value) : $value,
        };
    }

    private static function validRegex(string $pattern): string
    {
        Regex::assertValid($pattern);

        return $pattern;
    }

    private static function lower(string $value): string
    {
        return \function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
