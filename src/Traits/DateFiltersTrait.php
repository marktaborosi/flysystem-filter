<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use DateInterval;
use DateTimeInterface;
use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\Comparison;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\LastModifiedCondition;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Support\Time;

/**
 * Filters on the last modification time (`$item->lastModified()`).
 *
 * Dates can be UNIX timestamps, any {@see DateTimeInterface} (Carbon, CarbonImmutable,
 * DateTimeImmutable, …) or date strings (`'2025-01-31'`, `'yesterday'`). Items without a
 * known modification time never match.
 *
 * @internal used by {@see FilterBuilder}
 */
trait DateFiltersTrait
{
    /**
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    /**
     * Modified strictly before the date.
     *
     * @throws InvalidArgumentException for an invalid date
     */
    public function lastModifiedBefore(int|string|DateTimeInterface $date): static
    {
        return $this->push(new LastModifiedCondition(Comparison::LessThan, Time::toTimestamp($date)));
    }

    /**
     * Modified strictly after the date.
     *
     * @throws InvalidArgumentException for an invalid date
     */
    public function lastModifiedAfter(int|string|DateTimeInterface $date): static
    {
        return $this->push(new LastModifiedCondition(Comparison::GreaterThan, Time::toTimestamp($date)));
    }

    /**
     * Modified within the range, both ends inclusive.
     *
     * @throws InvalidArgumentException for an invalid date or when start > end
     */
    public function lastModifiedBetween(int|string|DateTimeInterface $start, int|string|DateTimeInterface $end): static
    {
        $from = Time::toTimestamp($start);
        $to = Time::toTimestamp($end);

        if ($from > $to) {
            throw new InvalidArgumentException('lastModifiedBetween(): the start date is after the end date.');
        }

        return $this->push(new AllOf(
            new LastModifiedCondition(Comparison::GreaterThanOrEqual, $from),
            new LastModifiedCondition(Comparison::LessThanOrEqual, $to),
        ));
    }

    /**
     * Modified within the last interval, e.g. `lastModifiedWithin('7 days')`, `lastModifiedWithin('PT12H')`.
     * The reference time is taken when this method is called.
     *
     * @throws InvalidArgumentException for an invalid interval
     */
    public function lastModifiedWithin(DateInterval|string $interval, ?DateTimeInterface $now = null): static
    {
        return $this->push(new LastModifiedCondition(Comparison::GreaterThanOrEqual, Time::ago($interval, $now)));
    }

    /**
     * Not modified for longer than the interval, e.g. `lastModifiedOlderThan('30 days')`.
     * The reference time is taken when this method is called.
     *
     * @throws InvalidArgumentException for an invalid interval
     */
    public function lastModifiedOlderThan(DateInterval|string $interval, ?DateTimeInterface $now = null): static
    {
        return $this->push(new LastModifiedCondition(Comparison::LessThan, Time::ago($interval, $now)));
    }
}
