<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Logical OR: matches when at least one child condition matches.
 *
 * Evaluation short-circuits on the first matching child. An empty AnyOf matches nothing.
 */
final readonly class AnyOf implements SerializableCondition
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
            if ($condition->matches($item)) {
                return true;
            }
        }

        return false;
    }

    public function describe(): string
    {
        if ($this->conditions === []) {
            return 'nothing';
        }

        return implode(' OR ', array_map(
            static fn(Condition $condition): string => $condition->describe(),
            $this->conditions,
        ));
    }

    public function toArray(): array
    {
        return [
            'type' => 'any',
            'conditions' => ConditionArrays::export($this->conditions),
        ];
    }
}
