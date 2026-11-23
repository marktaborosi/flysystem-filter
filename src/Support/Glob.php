<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Support;

use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;

/**
 * Converts glob patterns into anchored regular expressions.
 *
 * Supported syntax:
 * - `*`      any characters except `/`
 * - `**`     any characters including `/`; `**` followed by a slash also matches zero directories
 * - `?`      exactly one character except `/`
 * - `[abc]`, `[a-z]`, `[!abc]` / `[^abc]` character classes
 * - `{a,b}`  alternatives (may be nested and contain other glob syntax)
 * - `\x`     escapes the next character
 *
 * @internal
 */
final class Glob
{
    public static function toRegex(string $glob, bool $ignoreCase = false): string
    {
        $regex = '';
        $length = \strlen($glob);
        $braceDepth = 0;

        for ($i = 0; $i < $length; ++$i) {
            $char = $glob[$i];

            switch ($char) {
                case '\\':
                    $regex .= $i + 1 < $length ? preg_quote($glob[++$i], '#') : '\\\\';
                    break;

                case '*':
                    if (($glob[$i + 1] ?? '') === '*') {
                        ++$i;
                        if (($glob[$i + 1] ?? '') === '/') {
                            ++$i;
                            $regex .= '(?:.*/)?';
                        } else {
                            $regex .= '.*';
                        }
                    } else {
                        $regex .= '[^/]*';
                    }
                    break;

                case '?':
                    $regex .= '[^/]';
                    break;

                case '[':
                    $end = self::findClassEnd($glob, $i);
                    if ($end === null) {
                        $regex .= '\\[';
                        break;
                    }

                    $class = substr($glob, $i + 1, $end - $i - 1);
                    if ($class !== '' && ($class[0] === '!' || $class[0] === '^')) {
                        $class = '^' . substr($class, 1);
                    }
                    $regex .= '[' . str_replace(['\\', '#'], ['\\\\', '\\#'], $class) . ']';
                    $i = $end;
                    break;

                case '{':
                    ++$braceDepth;
                    $regex .= '(?:';
                    break;

                case '}':
                    if ($braceDepth > 0) {
                        --$braceDepth;
                        $regex .= ')';
                    } else {
                        $regex .= '\\}';
                    }
                    break;

                case ',':
                    $regex .= $braceDepth > 0 ? '|' : ',';
                    break;

                default:
                    $regex .= preg_quote($char, '#');
            }
        }

        if ($braceDepth > 0) {
            throw new InvalidArgumentException(\sprintf('Invalid glob pattern "%s": unclosed "{".', $glob));
        }

        return '#^' . $regex . '$#' . ($ignoreCase ? 'i' : '');
    }

    private static function findClassEnd(string $glob, int $start): ?int
    {
        $length = \strlen($glob);
        $i = $start + 1;

        // A closing bracket directly after "[" or "[!" is a literal.
        if ($i < $length && ($glob[$i] === '!' || $glob[$i] === '^')) {
            ++$i;
        }
        if ($i < $length && $glob[$i] === ']') {
            ++$i;
        }

        for (; $i < $length; ++$i) {
            if ($glob[$i] === ']') {
                return $i;
            }
        }

        return null;
    }
}
