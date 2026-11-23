<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Serialization;

use BackedEnum;
use Closure;
use League\MimeTypeDetection\MimeTypeDetector;
use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\AnyOf;
use Marktaborosi\FlysystemFilter\Condition\Comparison;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\DepthCondition;
use Marktaborosi\FlysystemFilter\Condition\HasMetadata;
use Marktaborosi\FlysystemFilter\Condition\IsDirectory;
use Marktaborosi\FlysystemFilter\Condition\IsFile;
use Marktaborosi\FlysystemFilter\Condition\IsHidden;
use Marktaborosi\FlysystemFilter\Condition\IsKnown;
use Marktaborosi\FlysystemFilter\Condition\LastModifiedCondition;
use Marktaborosi\FlysystemFilter\Condition\MetadataEquals;
use Marktaborosi\FlysystemFilter\Condition\Not;
use Marktaborosi\FlysystemFilter\Condition\SerializableCondition;
use Marktaborosi\FlysystemFilter\Condition\SizeCondition;
use Marktaborosi\FlysystemFilter\Condition\StringCondition;
use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;

/**
 * Turns arrays produced by `SerializableCondition::toArray()` back into conditions.
 *
 * Register your own condition types with {@see register()} to make custom
 * {@see SerializableCondition} implementations round-trip.
 *
 * Input is validated strictly and never executes code, so it can be fed with filters
 * coming from an HTTP request. User supplied regular expressions can still be slow
 * (catastrophic backtracking); pass `allowRegex: false` for untrusted input.
 */
final class ConditionSerializer
{
    /** @var array<string, Closure(array<string, mixed>, self): Condition> */
    private array $types = [];

    public function __construct(
        private readonly ?MimeTypeDetector $mimeTypeDetector = null,
        private readonly bool $allowRegex = true,
    ) {}

    /**
     * @param callable(array<string, mixed>, self): Condition $factory
     */
    public function register(string $type, callable $factory): self
    {
        $this->types[$type] = $factory(...);

        return $this;
    }

    /**
     * @param array<mixed> $data
     *
     * @throws InvalidArgumentException
     */
    public function fromArray(array $data): Condition
    {
        $type = $data['type'] ?? null;

        if (!\is_string($type)) {
            throw new InvalidArgumentException('Invalid filter definition: missing "type".');
        }

        /** @var array<string, mixed> $data */
        if (isset($this->types[$type])) {
            return ($this->types[$type])($data, $this);
        }

        return match ($type) {
            'all' => new AllOf(...$this->children($data)),
            'any' => new AnyOf(...$this->children($data)),
            'not' => new Not($this->fromArray(self::arrayValue($data, 'condition'))),
            'string' => new StringCondition(
                self::enum(Subject::class, $data, 'subject'),
                $this->stringOperator($data),
                self::stringList($data, 'values'),
                self::bool($data, 'ignoreCase'),
                $this->mimeTypeDetector,
            ),
            'size' => new SizeCondition(self::enum(Comparison::class, $data, 'operator'), self::nonNegativeInt($data, 'bytes')),
            'lastModified' => new LastModifiedCondition(self::enum(Comparison::class, $data, 'operator'), self::int($data, 'timestamp')),
            'depth' => new DepthCondition(self::enum(Comparison::class, $data, 'operator'), self::nonNegativeInt($data, 'depth')),
            'isFile' => new IsFile(),
            'isDirectory' => new IsDirectory(),
            'isHidden' => new IsHidden(),
            'isKnown' => new IsKnown(self::enum(Subject::class, $data, 'subject'), $this->mimeTypeDetector),
            'hasMetadata' => new HasMetadata(self::string($data, 'key')),
            'metadataEquals' => new MetadataEquals(self::string($data, 'key'), array_values(self::arrayValue($data, 'values'))),
            default => throw new InvalidArgumentException(\sprintf('Invalid filter definition: unknown type "%s".', $type)),
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function stringOperator(array $data): StringOperator
    {
        $operator = self::enum(StringOperator::class, $data, 'operator');

        if ($operator === StringOperator::Regex && !$this->allowRegex) {
            throw new InvalidArgumentException('Invalid filter definition: regular expressions are not allowed.');
        }

        return $operator;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<Condition>
     */
    private function children(array $data): array
    {
        $children = [];

        foreach (self::arrayValue($data, 'conditions') as $child) {
            if (!\is_array($child)) {
                throw new InvalidArgumentException('Invalid filter definition: "conditions" must be a list of objects.');
            }
            $children[] = $this->fromArray($child);
        }

        return $children;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param class-string<T> $enum
     * @param array<string, mixed> $data
     *
     * @return T
     */
    private static function enum(string $enum, array $data, string $key): BackedEnum
    {
        $value = $data[$key] ?? null;
        $case = \is_string($value) ? $enum::tryFrom($value) : null;

        if ($case === null) {
            $allowed = array_map(static fn(BackedEnum $case): string => (string) $case->value, $enum::cases());

            throw new InvalidArgumentException(\sprintf(
                'Invalid filter definition: "%s" must be one of [%s].',
                $key,
                implode(', ', $allowed),
            ));
        }

        return $case;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<mixed>
     */
    private static function arrayValue(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (!\is_array($value)) {
            throw new InvalidArgumentException(\sprintf('Invalid filter definition: "%s" must be an array.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<string>
     */
    private static function stringList(array $data, string $key): array
    {
        $values = [];

        foreach (self::arrayValue($data, $key) as $value) {
            if (!\is_string($value)) {
                throw new InvalidArgumentException(\sprintf('Invalid filter definition: "%s" must be a list of strings.', $key));
            }
            $values[] = $value;
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (!\is_string($value)) {
            throw new InvalidArgumentException(\sprintf('Invalid filter definition: "%s" must be a string.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function int(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (!\is_int($value)) {
            throw new InvalidArgumentException(\sprintf('Invalid filter definition: "%s" must be an integer.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function nonNegativeInt(array $data, string $key): int
    {
        $value = self::int($data, $key);

        if ($value < 0) {
            throw new InvalidArgumentException(\sprintf('Invalid filter definition: "%s" cannot be negative.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function bool(array $data, string $key): bool
    {
        $value = $data[$key] ?? false;

        if (!\is_bool($value)) {
            throw new InvalidArgumentException(\sprintf('Invalid filter definition: "%s" must be a boolean.', $key));
        }

        return $value;
    }
}
