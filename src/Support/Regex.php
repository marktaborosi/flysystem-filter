<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Support;

use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;

/**
 * @internal
 */
final class Regex
{
    /**
     * Ensures the pattern compiles, so mistakes surface when the filter is built
     * instead of producing a warning (and a silent non-match) for every item.
     *
     * @throws InvalidArgumentException
     */
    public static function assertValid(string $pattern): void
    {
        $error = null;
        set_error_handler(static function (int $level, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });

        try {
            $result = preg_match($pattern, '');
        } finally {
            restore_error_handler();
        }

        if ($result === false) {
            $reason = $error !== null
                ? preg_replace('/^preg_match\(\): /', '', $error)
                : preg_last_error_msg();

            throw new InvalidArgumentException(\sprintf('Invalid regular expression "%s": %s', $pattern, $reason));
        }
    }
}
