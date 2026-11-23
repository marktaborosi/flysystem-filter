<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Support;

use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;

/**
 * Parses and formats human-readable file sizes.
 *
 * Units are binary (1K = 1024 bytes), matching PHP's ini notation and earlier
 * versions of this package. Accepted forms (case-insensitive, optional space):
 * `512`, `512B`, `10K`, `10KB`, `10KiB`, `1.5M`, `2 GB`, `1T`, `1P`.
 *
 * @internal
 */
final class Size
{
    private const UNITS = ['B' => 0, 'K' => 1, 'M' => 2, 'G' => 3, 'T' => 4, 'P' => 5];

    /**
     * @throws InvalidArgumentException
     */
    public static function toBytes(int|string $size): int
    {
        if (\is_int($size)) {
            if ($size < 0) {
                throw new InvalidArgumentException(\sprintf('Size cannot be negative, %d given.', $size));
            }

            return $size;
        }

        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*(?:([KMGTP])(?:i?B)?|B)?\s*$/i', $size, $matches) !== 1) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid size value "%s". Use bytes (1024) or a number with a unit: B, K/KB/KiB, M, G, T, P (e.g. "1.5M").',
                $size,
            ));
        }

        $unit = strtoupper($matches[2] ?? '');
        $exponent = $unit === '' ? 0 : self::UNITS[$unit];

        return (int) round((float) $matches[1] * 1024 ** $exponent);
    }

    public static function format(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $exponent = min((int) floor(log($bytes, 1024)), 5);
        $value = $bytes / 1024 ** $exponent;
        $unit = array_search($exponent, self::UNITS, true);

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . ' ' . $unit . 'iB';
    }
}
