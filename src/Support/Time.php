<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Support;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Throwable;

/**
 * @internal
 */
final class Time
{
    /**
     * Converts a UNIX timestamp, any {@see DateTimeInterface} (Carbon, CarbonImmutable, DateTimeImmutable, …)
     * or a date string understood by PHP (`'2025-01-31'`, `'yesterday'`, `'-2 weeks'`) to a timestamp.
     *
     * @throws InvalidArgumentException
     */
    public static function toTimestamp(int|string|DateTimeInterface $value): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        try {
            return (new DateTimeImmutable($value))->getTimestamp();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(\sprintf('Invalid date "%s".', $value), 0, $exception);
        }
    }

    /**
     * Returns the timestamp `$interval` before `$now` (default: the current time).
     *
     * @param DateInterval|string $interval e.g. `'7 days'`, `'1 week 2 days'`, `'PT12H'` or a DateInterval
     *
     * @throws InvalidArgumentException
     */
    public static function ago(DateInterval|string $interval, ?DateTimeInterface $now = null): int
    {
        $interval = \is_string($interval) ? self::parseInterval($interval) : $interval;
        $now = $now instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($now) : new DateTimeImmutable();

        return $now->sub($interval)->getTimestamp();
    }

    private const UNITS = [
        'y' => 'years', 'yr' => 'years', 'yrs' => 'years', 'year' => 'years', 'years' => 'years',
        'mo' => 'months', 'month' => 'months', 'months' => 'months',
        'w' => 'weeks', 'week' => 'weeks', 'weeks' => 'weeks',
        'd' => 'days', 'day' => 'days', 'days' => 'days',
        'h' => 'hours', 'hr' => 'hours', 'hrs' => 'hours', 'hour' => 'hours', 'hours' => 'hours',
        'm' => 'minutes', 'min' => 'minutes', 'mins' => 'minutes', 'minute' => 'minutes', 'minutes' => 'minutes',
        's' => 'seconds', 'sec' => 'seconds', 'secs' => 'seconds', 'second' => 'seconds', 'seconds' => 'seconds',
    ];

    /**
     * Parses an ISO 8601 duration (`P7D`, `PT12H`) or a human readable interval such as
     * `7 days`, `1 week 2 days`, `2h 30m`, `1 year, 6 months`.
     */
    private static function parseInterval(string $interval): DateInterval
    {
        $trimmed = trim($interval);
        $iso = str_starts_with($trimmed, 'P') ? $trimmed : self::toIsoDuration($trimmed);

        try {
            $parsed = $iso === null ? null : new DateInterval($iso);
        } catch (Throwable) {
            $parsed = null;
        }

        if (!$parsed instanceof DateInterval || self::isZero($parsed)) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid interval "%s". Use e.g. "7 days", "2 weeks", "12 hours", "1 week 2 days" or an ISO 8601 duration like "P7D".',
                $interval,
            ));
        }

        return $parsed;
    }

    private static function toIsoDuration(string $interval): ?string
    {
        $pattern = '/\G\s*(?:,|and)?\s*(\d+)\s*([a-z]+)\s*/i';
        $offset = 0;
        $totals = ['years' => 0, 'months' => 0, 'weeks' => 0, 'days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0];

        while ($offset < \strlen($interval) && preg_match($pattern, $interval, $match, 0, $offset) === 1) {
            $unit = self::UNITS[strtolower($match[2])] ?? null;

            if ($unit === null) {
                return null;
            }

            $totals[$unit] += (int) $match[1];
            $offset += \strlen($match[0]);
        }

        if ($offset === 0 || $offset < \strlen($interval)) {
            return null;
        }

        $date = ($totals['years'] > 0 ? $totals['years'] . 'Y' : '')
            . ($totals['months'] > 0 ? $totals['months'] . 'M' : '')
            . ($totals['weeks'] * 7 + $totals['days'] > 0 ? ($totals['weeks'] * 7 + $totals['days']) . 'D' : '');
        $time = ($totals['hours'] > 0 ? $totals['hours'] . 'H' : '')
            . ($totals['minutes'] > 0 ? $totals['minutes'] . 'M' : '')
            . ($totals['seconds'] > 0 ? $totals['seconds'] . 'S' : '');

        if ($date === '' && $time === '') {
            return null;
        }

        return 'P' . $date . ($time !== '' ? 'T' . $time : '');
    }

    private static function isZero(DateInterval $interval): bool
    {
        return $interval->y === 0 && $interval->m === 0 && $interval->d === 0
            && $interval->h === 0 && $interval->i === 0 && $interval->s === 0 && (float) $interval->f === 0.0;
    }
}
