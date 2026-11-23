<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Exception;

use LogicException;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\SerializableCondition;

/**
 * Thrown when a filter that contains a closure (or another non-serializable condition)
 * is exported with `toArray()` / `json_encode()`.
 */
final class UnserializableConditionException extends LogicException implements FilterException
{
    public static function for(Condition $condition): self
    {
        return new self(\sprintf(
            'The condition "%s" (%s) cannot be serialized. Implement %s to make it exportable.',
            $condition->describe(),
            $condition::class,
            SerializableCondition::class,
        ));
    }
}
