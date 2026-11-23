<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

/**
 * How a {@see StringCondition} compares the subject with its values.
 */
enum StringOperator: string
{
    case Equals = 'equals';
    case Contains = 'contains';
    case StartsWith = 'startsWith';
    case EndsWith = 'endsWith';
    /** The value is a complete PCRE pattern including delimiters, e.g. `/^report-\d+$/i`. */
    case Regex = 'regex';
    /** The value is a glob pattern: `*`, `?`, `**`, `[abc]`, `{a,b}`. */
    case Glob = 'glob';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'equals',
            self::Contains => 'contains',
            self::StartsWith => 'starts with',
            self::EndsWith => 'ends with',
            self::Regex => 'matches regex',
            self::Glob => 'matches glob',
        };
    }

    /**
     * Whether the values are patterns that are compiled to regular expressions.
     */
    public function isPattern(): bool
    {
        return $this === self::Regex || $this === self::Glob;
    }
}
