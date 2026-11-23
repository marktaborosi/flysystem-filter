<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Logical NOT: inverts the result of the wrapped condition.
 */
final readonly class Not implements SerializableCondition
{
    public function __construct(public Condition $condition) {}

    public function matches(StorageAttributes $item): bool
    {
        return !$this->condition->matches($item);
    }

    public function describe(): string
    {
        $inner = $this->condition->describe();

        return $this->condition instanceof AllOf || $this->condition instanceof AnyOf
            ? 'NOT (' . $inner . ')'
            : 'NOT ' . $inner;
    }

    public function toArray(): array
    {
        return [
            'type' => 'not',
            'condition' => ConditionArrays::export([$this->condition])[0],
        ];
    }
}
