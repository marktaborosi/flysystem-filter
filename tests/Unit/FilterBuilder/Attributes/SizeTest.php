<?php

namespace FilterBuilder\Attributes;

use League\Flysystem\FileAttributes;
use LogicException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class SizeTest
 *
 * Unit tests for file size-related filtering in the FilterBuilder.
 * Tests cover all supported comparisons: equality, inequality, range checks,
 * and human-readable formats (e.g., KB, MB, GB, TB).
 */
class SizeTest extends TestCase
{
    /**
     * Test filtering by exact file size using an integer.
     *
     * @throws Exception
     */
    public function test_size_equals()
    {
        $filter = new FilterBuilder();
        $filter->sizeEquals(1024);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1024);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by exact file size using a formatted string (e.g. "1M").
     *
     * @throws Exception
     */
    public function test_size_equals_with_string_format()
    {
        $filter = new FilterBuilder();
        $filter->sizeEquals('1M');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1024 * 1024);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size not equals.
     *
     * @throws Exception
     */
    public function test_size_not_equals()
    {
        $filter = new FilterBuilder();
        $filter->sizeNotEquals(2048);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1024);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size greater than.
     *
     * @throws Exception
     */
    public function test_size_gt()
    {
        $filter = new FilterBuilder();
        $filter->sizeGt(1000);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(2000);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size greater than or equal to.
     *
     * @throws Exception
     */
    public function test_size_gte()
    {
        $filter = new FilterBuilder();
        $filter->sizeGte(2048);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(2048);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size less than.
     *
     * @throws Exception
     */
    public function test_size_lt()
    {
        $filter = new FilterBuilder();
        $filter->sizeLt(1000);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(512);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size less than or equal to.
     *
     * @throws Exception
     */
    public function test_size_lte()
    {
        $filter = new FilterBuilder();
        $filter->sizeLte(4096);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(4096);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size between two values (int-based).
     *
     * @throws Exception
     */
    public function test_size_between()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween(100, 200);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(150);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by size range using byte format (e.g., 100B–200B).
     *
     * @throws Exception
     */
    public function test_size_between_with_bytes()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween('100B', '200B');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(150);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by kilobyte range.
     *
     * @throws Exception
     */
    public function test_size_between_with_kilobytes()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween('1K', '2K');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1500);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by megabyte range.
     *
     * @throws Exception
     */
    public function test_size_between_with_megabytes()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween('1M', '2M');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1500000);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by gigabyte range.
     *
     * @throws Exception
     */
    public function test_size_between_with_gigabytes()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween('1G', '2G');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1073741824); // 1GB

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by terabyte range.
     *
     * @throws Exception
     */
    public function test_size_between_with_terabytes()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween('1T', '2T');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1024 * 1024 * 1024 * 1024 + 1);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test invalid size format throws LogicException.
     *
     */
    public function test_size_between_with_bad_unit_throws_logic_exception()
    {
        $this->expectException(LogicException::class);

        $filter = new FilterBuilder();
        $filter->sizeBetween('1F', '2F'); // Invalid units
    }

    /**
     * Test that size filters return false when fileSize is null.
     *
     * @throws Exception
     */
    public function test_size_null_returns_false()
    {
        $filter = new FilterBuilder();
        $filter->sizeGt(100);

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(null);

        $this->assertFalse($filter->matches($item));
    }
}
