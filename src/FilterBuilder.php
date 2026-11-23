<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter;

use BadMethodCallException;
use Closure;
use JsonException;
use JsonSerializable;
use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\MimeTypeDetector;
use Marktaborosi\FlysystemFilter\Condition\AnyOf;
use Marktaborosi\FlysystemFilter\Condition\Callback;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\ConditionArrays;
use Marktaborosi\FlysystemFilter\Condition\Not;
use Marktaborosi\FlysystemFilter\Condition\SerializableCondition;
use Marktaborosi\FlysystemFilter\Condition\StringCondition;
use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\Exception\InvalidFilterException;
use Marktaborosi\FlysystemFilter\Internal\Group;
use Marktaborosi\FlysystemFilter\Serialization\ConditionSerializer;
use Marktaborosi\FlysystemFilter\Support\MimeTypes;
use Marktaborosi\FlysystemFilter\Traits\BasenameFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\CustomFilterTrait;
use Marktaborosi\FlysystemFilter\Traits\DateFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\DepthFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\ExtensionFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\FilenameFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\FileTypeFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\MetadataFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\MimeTypeFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\PathFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\SizeFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\VisibilityFiltersTrait;
use ReflectionFunction;
use ReflectionNamedType;
use Stringable;

/**
 * Fluent builder for Flysystem filters.
 *
 * ```php
 * $filter = FilterBuilder::create()
 *     ->isFile()
 *     ->where(fn (FilterBuilder $q) => $q->extensionEquals('log')->or()->pathMatchesGlob('debug/**'))
 *     ->not()->isHidden();
 *
 * $listing->filter($filter);            // native Flysystem
 * (new FlysystemFilter())->filter($listing, $filter);
 * ```
 *
 * Conditions are joined with AND unless `or()` is called; AND binds tighter than OR, and
 * `groupStart()`/`groupEnd()` (or `where(Closure)`) act as parentheses. The builder compiles
 * into an immutable tree of {@see Condition} objects that is evaluated with short-circuiting.
 * Malformed expressions throw an {@see InvalidFilterException} at the offending call.
 */
final class FilterBuilder implements SerializableCondition, JsonSerializable, Stringable
{
    use BasenameFiltersTrait;
    use CustomFilterTrait;
    use DateFiltersTrait;
    use DepthFiltersTrait;
    use ExtensionFiltersTrait;
    use FilenameFiltersTrait;
    use FileTypeFiltersTrait;
    use MetadataFiltersTrait;
    use MimeTypeFiltersTrait;
    use PathFiltersTrait;
    use SizeFiltersTrait;
    use VisibilityFiltersTrait;

    /** @var array<string, Closure> */
    private static array $macros = [];

    private Group $root;

    /** @var list<Group> Currently open (nested) groups, innermost last. */
    private array $open = [];

    private ?Condition $compiled = null;

    private readonly MimeTypeDetector $mimeTypeDetector;

    /**
     * @param MimeTypeDetector|null $mimeTypeDetector Used by the MIME type filters when the adapter
     *                                                did not provide a MIME type. Defaults to an
     *                                                extension based detector (no file reads).
     */
    public function __construct(?MimeTypeDetector $mimeTypeDetector = null)
    {
        $this->mimeTypeDetector = $mimeTypeDetector ?? MimeTypes::defaultDetector();
        $this->root = new Group();
    }

    public static function create(?MimeTypeDetector $mimeTypeDetector = null): self
    {
        return new self($mimeTypeDetector);
    }

    /**
     * Rebuilds a filter exported with {@see toArray()}.
     *
     * @param array<mixed> $data
     *
     * @throws InvalidArgumentException when the definition is invalid
     */
    public static function fromArray(array $data, ?ConditionSerializer $serializer = null, ?MimeTypeDetector $mimeTypeDetector = null): self
    {
        $serializer ??= new ConditionSerializer($mimeTypeDetector);

        return (new self($mimeTypeDetector))->where($serializer->fromArray($data));
    }

    /**
     * Rebuilds a filter exported with `json_encode($builder)`.
     *
     * @throws InvalidArgumentException when the JSON or the definition is invalid
     */
    public static function fromJson(string $json, ?ConditionSerializer $serializer = null, ?MimeTypeDetector $mimeTypeDetector = null): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid filter JSON: ' . $exception->getMessage(), 0, $exception);
        }

        if (!\is_array($data)) {
            throw new InvalidArgumentException('Invalid filter JSON: an object is expected.');
        }

        return self::fromArray($data, $serializer, $mimeTypeDetector);
    }

    // ---------------------------------------------------------------------
    // Logical operators and grouping
    // ---------------------------------------------------------------------

    /**
     * Joins the previous and the next condition with AND. Optional: AND is the default.
     *
     * @throws InvalidFilterException when no condition precedes it
     */
    public function and(): static
    {
        return $this->operator(Group::AND);
    }

    /**
     * Joins the previous and the next condition with OR.
     *
     * @throws InvalidFilterException when no condition precedes it
     */
    public function or(): static
    {
        return $this->operator(Group::OR);
    }

    /**
     * Negates the next condition or group: `->not()->isHidden()`, `->not()->groupStart()...`.
     *
     * @throws InvalidFilterException when called twice in a row
     */
    public function not(): static
    {
        $group = $this->current();

        if ($group->pendingNot) {
            throw new InvalidFilterException('not() cannot be called twice in a row; it must be followed by a condition or a group.');
        }

        $group->pendingNot = true;
        $this->compiled = null;

        return $this;
    }

    /**
     * Opens a group (an opening parenthesis).
     */
    public function groupStart(): static
    {
        $parent = $this->current();
        $this->open[] = new Group($parent->pendingOperator, $parent->pendingNot);
        $parent->pendingOperator = null;
        $parent->pendingNot = false;
        $this->compiled = null;

        return $this;
    }

    /**
     * Closes the most recently opened group (a closing parenthesis).
     *
     * @throws InvalidFilterException when no group is open, or the group is empty or ends with an operator
     */
    public function groupEnd(): static
    {
        $group = array_pop($this->open);

        if ($group === null) {
            throw new InvalidFilterException('Mismatched groupEnd(): there is no open group to close.');
        }

        if ($group->isEmpty()) {
            throw new InvalidFilterException('Empty group: add at least one condition between groupStart() and groupEnd().');
        }

        $this->assertNothingPending($group, 'groupEnd()');

        $condition = $group->toCondition();
        $parent = $this->current();
        $parent->pendingOperator = $group->attachOperator;
        $parent->add($group->negated ? new Not($condition) : $condition);
        $this->compiled = null;

        return $this;
    }

    /**
     * @deprecated since 4.0, use {@see groupStart()}
     */
    public function group_start(): static
    {
        return $this->groupStart();
    }

    /**
     * @deprecated since 4.0, use {@see groupEnd()}
     */
    public function group_end(): static
    {
        return $this->groupEnd();
    }

    /**
     * Adds a condition, a nested group or a predicate (joined with AND, unless `or()` was called).
     *
     * - `Condition` (including another `FilterBuilder`, which is snapshotted)
     * - `Closure(FilterBuilder): mixed`: builds a parenthesised group
     * - `Closure(StorageAttributes): bool` (first parameter type-hinted with a Flysystem
     *   attributes class): used as a custom predicate, like {@see custom()}
     *
     * A group closure that adds no conditions is ignored.
     *
     * @param Condition|Closure $condition a condition, a `Closure(FilterBuilder)` group or a `Closure(StorageAttributes): bool` predicate
     */
    public function where(Condition|Closure $condition): static
    {
        if ($condition instanceof Closure) {
            if ($this->isPredicate($condition)) {
                return $this->push(new Callback($condition));
            }

            $nested = new self($this->mimeTypeDetector);
            $condition($nested);

            if (!$nested->hasConditions()) {
                $group = $this->current();
                $group->pendingOperator = null;
                $group->pendingNot = false;

                return $this;
            }

            $condition = $nested;
        }

        return $this->push($condition instanceof self ? $condition->toCondition() : $condition);
    }

    /**
     * Same as `or()->where(...)`. On an empty builder or group it behaves like `where()`,
     * which makes it convenient in loops.
     *
     * @param Condition|Closure $condition see {@see where()}
     */
    public function orWhere(Condition|Closure $condition): static
    {
        return $this->current()->isEmpty() ? $this->where($condition) : $this->or()->where($condition);
    }

    /**
     * Same as `not()->where(...)`.
     *
     * @param Condition|Closure $condition see {@see where()}
     */
    public function whereNot(Condition|Closure $condition): static
    {
        return $this->not()->where($condition);
    }

    /**
     * Same as `or()->not()->where(...)`.
     *
     * @param Condition|Closure $condition see {@see where()}
     */
    public function orWhereNot(Condition|Closure $condition): static
    {
        return $this->current()->isEmpty() ? $this->whereNot($condition) : $this->or()->whereNot($condition);
    }

    /**
     * Matches when at least one of the given conditions/groups matches. An empty list is ignored.
     *
     * @param iterable<Condition|Closure> $conditions see {@see where()}
     */
    public function whereAny(iterable $conditions): static
    {
        $compiled = $this->compileAll($conditions);

        if ($compiled === []) {
            return $this;
        }

        return $this->push(\count($compiled) === 1 ? $compiled[0] : new AnyOf(...$compiled));
    }

    /**
     * Matches when all of the given conditions/groups match. An empty list is ignored.
     *
     * @param iterable<Condition|Closure> $conditions see {@see where()}
     */
    public function whereAll(iterable $conditions): static
    {
        return $this->where(function (self $group) use ($conditions): void {
            foreach ($conditions as $condition) {
                $group->where($condition);
            }
        });
    }

    /**
     * Applies the callback when `$value` is truthy (or the default callback otherwise).
     * The callbacks receive the builder itself, so they continue the current expression.
     *
     * ```php
     * $filter->isFile()->when($onlyLogs, fn ($q) => $q->extensionEquals('log'));
     * ```
     *
     * @param mixed|Closure(self): mixed $value evaluated for truthiness
     * @param Closure(self, mixed): mixed $callback
     * @param (Closure(self, mixed): mixed)|null $default
     */
    public function when(mixed $value, Closure $callback, ?Closure $default = null): static
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if ($this->isTruthy($value)) {
            $callback($this, $value);
        } elseif ($default instanceof Closure) {
            $default($this, $value);
        }

        return $this;
    }

    /**
     * Inverse of {@see when()}.
     *
     * @param mixed|Closure(self): mixed $value evaluated for truthiness
     * @param Closure(self, mixed): mixed $callback
     * @param (Closure(self, mixed): mixed)|null $default
     */
    public function unless(mixed $value, Closure $callback, ?Closure $default = null): static
    {
        $value = $value instanceof Closure ? $value($this) : $value;

        if (!$this->isTruthy($value)) {
            $callback($this, $value);
        } elseif ($default instanceof Closure) {
            $default($this, $value);
        }

        return $this;
    }

    // ---------------------------------------------------------------------
    // Evaluation & export
    // ---------------------------------------------------------------------

    /**
     * Whether the given item matches the filter. An empty builder matches everything.
     *
     * @throws InvalidFilterException when the expression is incomplete (open group, dangling operator)
     */
    public function matches(StorageAttributes $item): bool
    {
        return $this->toCondition()->matches($item);
    }

    /**
     * Allows passing the builder directly to Flysystem: `$listing->filter($builder)`.
     */
    public function __invoke(StorageAttributes $item): bool
    {
        return $this->matches($item);
    }

    /**
     * Compiles the builder into an immutable condition tree (cached until the builder changes).
     *
     * @throws InvalidFilterException when the expression is incomplete (open group, dangling operator)
     */
    public function toCondition(): Condition
    {
        if ($this->compiled instanceof Condition) {
            return $this->compiled;
        }

        if ($this->open !== []) {
            throw new InvalidFilterException(\sprintf(
                'Unbalanced groups: missing groupEnd() for %d open group(s).',
                \count($this->open),
            ));
        }

        $this->assertNothingPending($this->root, 'the end of the filter');

        return $this->compiled = $this->root->toCondition();
    }

    /**
     * Whether at least one condition has been added.
     */
    public function hasConditions(): bool
    {
        return $this->open !== [] || !$this->root->isEmpty();
    }

    /**
     * Human-readable form of the filter, e.g. `is file AND (extension equals log OR path matches glob debug/**)`.
     */
    public function describe(): string
    {
        return $this->toCondition()->describe();
    }

    public function __toString(): string
    {
        return $this->describe();
    }

    /**
     * Exports the filter to a plain array that {@see fromArray()} can read back.
     *
     * @throws Exception\UnserializableConditionException when the filter contains closures
     */
    public function toArray(): array
    {
        return ConditionArrays::export([$this->toCondition()])[0];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // ---------------------------------------------------------------------
    // Macros
    // ---------------------------------------------------------------------

    /**
     * Registers a reusable, named filter method. The closure receives the builder as its
     * first argument, followed by the arguments of the call.
     *
     * ```php
     * FilterBuilder::macro('isLog', static fn (FilterBuilder $q) => $q->where(
     *     static fn (FilterBuilder $g) => $g->extensionEquals(['log', 'gz'])->and()->pathStartsWith('logs/'),
     * ));
     *
     * FilterBuilder::macro('olderThanDays', static fn (FilterBuilder $q, int $days) => $q->lastModifiedOlderThan("$days days"));
     *
     * FilterBuilder::create()->isLog()->or()->isImage();
     * ```
     *
     * @param Closure $macro `Closure(FilterBuilder $builder, mixed ...$arguments): mixed`
     */
    public static function macro(string $name, Closure $macro): void
    {
        if (method_exists(self::class, $name)) {
            throw new InvalidArgumentException(\sprintf('Cannot register macro "%s": a method with that name already exists.', $name));
        }

        self::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(self::$macros[$name]);
    }

    public static function flushMacros(): void
    {
        self::$macros = [];
    }

    /**
     * @param array<mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!isset(self::$macros[$name])) {
            throw new BadMethodCallException(\sprintf('Method %s::%s() does not exist.', self::class, $name));
        }

        $result = (self::$macros[$name])($this, ...$arguments);

        return $result ?? $this;
    }

    public function __clone()
    {
        $this->root = clone $this->root;
        $this->open = array_map(static fn(Group $group): Group => clone $group, $this->open);
    }

    // ---------------------------------------------------------------------
    // Internals used by the filter traits
    // ---------------------------------------------------------------------

    /**
     * @param string|int|list<string|int> $values
     */
    protected function addStringCondition(
        Subject $subject,
        StringOperator $operator,
        string|int|array $values,
        bool $ignoreCase = false,
        bool $negate = false,
    ): static {
        $condition = new StringCondition($subject, $operator, $values, $ignoreCase, $this->mimeTypeDetector);

        return $this->push($negate ? new Not($condition) : $condition);
    }

    protected function push(Condition $condition): static
    {
        $group = $this->current();

        if ($group->pendingNot) {
            $condition = new Not($condition);
            $group->pendingNot = false;
        }

        $group->add($condition);
        $this->compiled = null;

        return $this;
    }

    /**
     * @param Group::AND|Group::OR $operator
     */
    private function operator(string $operator): static
    {
        $group = $this->current();
        $call = $operator . '()';

        if ($group->pendingNot) {
            throw new InvalidFilterException(\sprintf('Cannot call %s directly after not(); not() must be followed by a condition or a group.', $call));
        }

        if ($group->pendingOperator !== null) {
            throw new InvalidFilterException(\sprintf(
                'Cannot call %s directly after %s(); a condition is required between two logical operators.',
                $call,
                $group->pendingOperator,
            ));
        }

        if ($group->isEmpty()) {
            throw new InvalidFilterException(\sprintf(
                'Cannot call %s at the start of %s; it must follow a condition.',
                $call,
                $this->open !== [] ? 'a group' : 'the filter',
            ));
        }

        $group->pendingOperator = $operator;
        $this->compiled = null;

        return $this;
    }

    private function current(): Group
    {
        return $this->open === [] ? $this->root : $this->open[\count($this->open) - 1];
    }

    private function assertNothingPending(Group $group, string $where): void
    {
        if ($group->pendingOperator !== null) {
            throw new InvalidFilterException(\sprintf(
                'Dangling %s() before %s: it must be followed by a condition.',
                $group->pendingOperator,
                $where,
            ));
        }

        if ($group->pendingNot) {
            throw new InvalidFilterException(\sprintf('Dangling not() before %s: it must be followed by a condition.', $where));
        }
    }

    /**
     * @param iterable<Condition|Closure> $conditions see {@see where()}
     *
     * @return list<Condition>
     */
    private function compileAll(iterable $conditions): array
    {
        $compiled = [];

        foreach ($conditions as $condition) {
            $builder = (new self($this->mimeTypeDetector))->where($condition);

            if ($builder->hasConditions()) {
                $compiled[] = $builder->toCondition();
            }
        }

        return $compiled;
    }

    private function isTruthy(mixed $value): bool
    {
        return !\in_array($value, [null, false, 0, 0.0, '', '0', []], true);
    }

    private function isPredicate(Closure $closure): bool
    {
        $parameters = (new ReflectionFunction($closure))->getParameters();
        $type = isset($parameters[0]) ? $parameters[0]->getType() : null;

        return $type instanceof ReflectionNamedType
            && !$type->isBuiltin()
            && is_a($type->getName(), StorageAttributes::class, true);
    }
}
