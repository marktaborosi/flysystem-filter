<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Logical AND: matches when every child condition matches.
 *
 * Evaluation short-circuits on the first non-matching child. An empty AllOf matches everything.
 */
final readonly class AllOf implements SerializableCondition
{
    /** @var list<Condition> */
    public array $conditions;

    public function __construct(Condition ...$conditions)
    {
        $this->conditions = array_values($conditions);
    }

    public function matches(StorageAttributes $item): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->matches($item)) {
                return false;
            }
        }

        return true;
    }

    public function describe(): string
    {
        if ($this->conditions === []) {
            return 'everything';
        }

        return implode(' AND ', array_map(
            static fn(Condition $condition): string => $condition instanceof AnyOf && \count($condition->conditions) > 1
                ? '(' . $condition->describe() . ')'
                : $condition->describe(),
            $this->conditions,
        ));
    }

    public function toArray(): array
    {
        return [
            'type' => 'all',
            'conditions' => ConditionArrays::export($this->conditions),
        ];
    }
}
