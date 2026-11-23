<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use Marktaborosi\FlysystemFilter\Exception\UnserializableConditionException;
use Marktaborosi\FlysystemFilter\Serialization\ConditionSerializer;

/**
 * A condition that can be exported to a plain array (and therefore to JSON).
 *
 * The array must contain a `type` key that {@see ConditionSerializer}
 * knows how to turn back into a condition.
 */
interface SerializableCondition extends Condition
{
    /**
     * @return array<string, mixed>
     *
     * @throws UnserializableConditionException when the condition (or one of its children) cannot be exported
     */
    public function toArray(): array;
}
