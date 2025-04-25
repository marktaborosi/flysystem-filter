<?php

namespace FilterBuilder\Attributes;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class PathTest
 *
 * Unit tests for path-based filtering logic in the FilterBuilder.
 * These tests cover direct path matching, substring checks, exclusions,
 * and regular expression-based path filters.
 */
class PathTest extends TestCase
{
    /**
     * Test filtering by exact path match.
     *
     * @throws Exception
     */
    public function test_path_equals()
    {
        $filter = new FilterBuilder();
        $filter->pathEquals('/some/path');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/path');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by path containing a substring.
     *
     * @throws Exception
     */
    public function test_path_contains()
    {
        $filter = new FilterBuilder();
        $filter->pathContains('part');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/path/with/part');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by path not being equal to specified values.
     *
     * @throws Exception
     */
    public function test_path_not_equals()
    {
        $filter = new FilterBuilder();
        $filter->pathNotEquals(['/exclude/this.txt']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/include/that.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by path not containing specified substrings.
     *
     * @throws Exception
     */
    public function test_path_not_contains()
    {
        $filter = new FilterBuilder();
        $filter->pathNotContains(['tmp', 'draft']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/storage/public/notes/final.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by path using a regular expression match.
     *
     * @throws Exception
     */
    public function test_path_matches_regex()
    {
        $filter = new FilterBuilder();
        $filter->pathMatchesRegex('/\/logs\/\d{4}\/.*\.log$/');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/logs/2024/system-error.log');

        $this->assertTrue($filter->matches($item));
    }
}
