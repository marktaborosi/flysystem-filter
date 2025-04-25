<?php

namespace FilterBuilder\Attributes;

use Carbon\Carbon;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class LastModifiedTest
 *
 * Unit tests for the last-modified-related filters in the FilterBuilder.
 * These tests verify that date-based filtering works correctly using
 * exact timestamps and Carbon instances for before, after, and between conditions.
 */
class LastModifiedTest extends TestCase
{
    /**
     * Test filtering by last modified date between two timestamps.
     *
     * The file should match if its lastModified value falls within the specified range.
     *
     * @throws Exception
     */
    public function test_last_modified_between()
    {
        $filter = new FilterBuilder();
        $filter->lastModifiedBetween(Carbon::now()->subDays(10), Carbon::now());

        $item = $this->createMock(StorageAttributes::class);
        $item->method('lastModified')->willReturn(Carbon::now()->subDays(5)->timestamp);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by last modified date before a given timestamp.
     *
     * The file should match if its lastModified value is earlier than the specified time.
     *
     * @throws Exception
     */
    public function test_last_modified_before()
    {
        $filter = new FilterBuilder();
        $filter->lastModifiedBefore(Carbon::now()->subDays(1));

        $item = $this->createMock(StorageAttributes::class);
        $item->method('lastModified')->willReturn(Carbon::now()->subDays(3)->timestamp);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by last modified date after a given timestamp.
     *
     * The file should match if its lastModified value is later than the specified time.
     *
     * @throws Exception
     */
    public function test_last_modified_after()
    {
        $filter = new FilterBuilder();
        $filter->lastModifiedAfter(Carbon::now()->subDays(2));

        $item = $this->createMock(StorageAttributes::class);
        $item->method('lastModified')->willReturn(Carbon::now()->subHours(6)->timestamp);

        $this->assertTrue($filter->matches($item));
    }
}
