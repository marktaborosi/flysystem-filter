<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use Marktaborosi\FlysystemFilter\Exception\UnserializableConditionException;

/**
 * @internal
 */
final class ConditionArrays
{
    /**
     * @param list<Condition> $conditions
     *
     * @return list<array<string, mixed>>
     *
     * @throws UnserializableConditionException
     */
    public static function export(array $conditions): array
    {
        $exported = [];

        foreach ($conditions as $condition) {
            if (!$condition instanceof SerializableCondition) {
                throw UnserializableConditionException::for($condition);
            }

            $exported[] = $condition->toArray();
        }

        return $exported;
    }
}
