<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DateFiltersTraitTest extends TestCase
{
    private const NOON = 1_735_732_800; // 2025-01-01 12:00:00 UTC

    /**
     * @return iterable<string, array{int|string|DateTimeInterface}>
     */
    public static function dateFormats(): iterable
    {
        yield 'int timestamp' => [self::NOON];
        yield 'Carbon' => [Carbon::createFromTimestamp(self::NOON)];
        yield 'CarbonImmutable' => [CarbonImmutable::createFromTimestamp(self::NOON)];
        yield 'DateTime' => [(new DateTime())->setTimestamp(self::NOON)];
        yield 'DateTimeImmutable' => [(new DateTimeImmutable())->setTimestamp(self::NOON)];
        yield 'date string' => ['2025-01-01 12:00:00 UTC'];
    }

    #[Test]
    #[DataProvider('dateFormats')]
    public function accepts_every_date_type(int|string|DateTimeInterface $date): void
    {
        $before = (new FilterBuilder())->lastModifiedBefore($date);
        $after = (new FilterBuilder())->lastModifiedAfter($date);

        self::assertTrue($before->matches(Items::file('a', lastModified: self::NOON - 1)));
        self::assertFalse($before->matches(Items::file('a', lastModified: self::NOON)));
        self::assertTrue($after->matches(Items::file('a', lastModified: self::NOON + 1)));
        self::assertFalse($after->matches(Items::file('a', lastModified: self::NOON)));
    }

    #[Test]
    public function between_is_inclusive(): void
    {
        $filter = (new FilterBuilder())->lastModifiedBetween(self::NOON, self::NOON + 60);

        self::assertTrue($filter->matches(Items::file('a', lastModified: self::NOON)));
        self::assertTrue($filter->matches(Items::file('a', lastModified: self::NOON + 60)));
        self::assertFalse($filter->matches(Items::file('a', lastModified: self::NOON + 61)));
        self::assertTrue($filter->matches(Items::dir('d', lastModified: self::NOON + 1)));
    }

    #[Test]
    public function unknown_modification_time_never_matches(): void
    {
        $item = Items::file('a', lastModified: null);

        self::assertFalse((new FilterBuilder())->lastModifiedBefore(self::NOON)->matches($item));
        self::assertFalse((new FilterBuilder())->lastModifiedAfter(0)->matches($item));
        self::assertFalse((new FilterBuilder())->lastModifiedWithin('1 day')->matches($item));
    }

    #[Test]
    public function relative_intervals(): void
    {
        $now = (new DateTimeImmutable())->setTimestamp(self::NOON);
        $within = (new FilterBuilder())->lastModifiedWithin('7 days', $now);
        $older = (new FilterBuilder())->lastModifiedOlderThan(new DateInterval('P30D'), $now);

        self::assertTrue($within->matches(Items::file('a', lastModified: self::NOON - 6 * 86_400)));
        self::assertTrue($within->matches(Items::file('a', lastModified: self::NOON - 7 * 86_400)));
        self::assertFalse($within->matches(Items::file('a', lastModified: self::NOON - 8 * 86_400)));
        self::assertTrue($older->matches(Items::file('a', lastModified: self::NOON - 31 * 86_400)));
        self::assertFalse($older->matches(Items::file('a', lastModified: self::NOON - 29 * 86_400)));
    }

    #[Test]
    public function relative_intervals_support_iso_durations_and_compound_strings(): void
    {
        $now = (new DateTimeImmutable())->setTimestamp(self::NOON);

        self::assertTrue((new FilterBuilder())->lastModifiedWithin('PT12H', $now)->matches(Items::file('a', lastModified: self::NOON - 11 * 3600)));
        self::assertFalse((new FilterBuilder())->lastModifiedWithin('1 week 2 days', $now)->matches(Items::file('a', lastModified: self::NOON - 10 * 86_400)));
        self::assertTrue((new FilterBuilder())->lastModifiedWithin('1 week 2 days', $now)->matches(Items::file('a', lastModified: self::NOON - 8 * 86_400)));
        self::assertTrue((new FilterBuilder())->lastModifiedWithin('2h 30m', $now)->matches(Items::file('a', lastModified: self::NOON - 9000)));
        self::assertFalse((new FilterBuilder())->lastModifiedWithin('2h, 30 mins', $now)->matches(Items::file('a', lastModified: self::NOON - 9001)));
        self::assertTrue((new FilterBuilder())->lastModifiedOlderThan('1 year and 6 months', $now)->matches(Items::file('a', lastModified: self::NOON - 600 * 86_400)));
    }

    #[Test]
    public function relative_interval_defaults_to_now(): void
    {
        $filter = (new FilterBuilder())->lastModifiedWithin('1 hour');

        self::assertTrue($filter->matches(Items::file('a', lastModified: time() - 60)));
        self::assertFalse($filter->matches(Items::file('a', lastModified: time() - 7200)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIntervals(): iterable
    {
        yield 'garbage' => ['soon'];
        yield 'empty' => [''];
        yield 'invalid ISO' => ['P1X'];
        yield 'unknown unit' => ['3 fortnights'];
        yield 'trailing garbage' => ['3 days ago'];
        yield 'zero' => ['0 days'];
    }

    #[Test]
    #[DataProvider('invalidIntervals')]
    public function invalid_intervals_are_rejected(string $interval): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FilterBuilder())->lastModifiedWithin($interval);
    }

    #[Test]
    public function invalid_date_string_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date "not a date"');

        (new FilterBuilder())->lastModifiedAfter('not a date');
    }

    #[Test]
    public function between_rejects_inverted_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FilterBuilder())->lastModifiedBetween(self::NOON, self::NOON - 1);
    }
}
