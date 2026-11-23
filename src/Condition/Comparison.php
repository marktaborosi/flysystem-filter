<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

/**
 * Numeric comparison operators used by size, date and depth conditions.
 */
enum Comparison: string
{
    case Equal = '=';
    case NotEqual = '!=';
    case GreaterThan = '>';
    case GreaterThanOrEqual = '>=';
    case LessThan = '<';
    case LessThanOrEqual = '<=';

    public function compare(int $actual, int $expected): bool
    {
        return match ($this) {
            self::Equal => $actual === $expected,
            self::NotEqual => $actual !== $expected,
            self::GreaterThan => $actual > $expected,
            self::GreaterThanOrEqual => $actual >= $expected,
            self::LessThan => $actual < $expected,
            self::LessThanOrEqual => $actual <= $expected,
        };
    }
}
