<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Internal;

use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\AnyOf;
use Marktaborosi\FlysystemFilter\Condition\Condition;

/**
 * One level of parentheses while a filter is being built.
 *
 * Conditions are stored in disjunctive form: a list of OR-ed terms, where each term is a
 * list of AND-ed conditions. This gives AND a higher precedence than OR, exactly like
 * `&&` / `||` in PHP (and like the `eval()` based evaluator of v1–v3).
 *
 * @internal
 */
final class Group
{
    public const AND = 'and';
    public const OR = 'or';

    /** @var list<non-empty-list<Condition>> */
    private array $terms = [];

    /** @var self::AND|self::OR|null Operator called since the last condition. */
    public ?string $pendingOperator = null;

    /** Whether not() was called since the last condition. */
    public bool $pendingNot = false;

    /**
     * @param self::AND|self::OR|null $attachOperator operator that joins this group to the parent
     */
    public function __construct(
        public readonly ?string $attachOperator = null,
        public readonly bool $negated = false,
    ) {}

    public function isEmpty(): bool
    {
        return $this->terms === [];
    }

    /**
     * Adds a condition using the pending operator (implicit AND when none was given).
     */
    public function add(Condition $condition): void
    {
        $lastTerm = array_key_last($this->terms);

        if ($lastTerm === null || $this->pendingOperator === self::OR) {
            $this->terms[] = [$condition];
        } else {
            $this->terms[$lastTerm][] = $condition;
        }

        $this->pendingOperator = null;
    }

    public function toCondition(): Condition
    {
        $terms = array_map(
            static fn(array $term): Condition => \count($term) === 1 ? $term[0] : new AllOf(...$term),
            $this->terms,
        );

        return match (\count($terms)) {
            0 => new AllOf(),
            1 => $terms[0],
            default => new AnyOf(...$terms),
        };
    }
}
