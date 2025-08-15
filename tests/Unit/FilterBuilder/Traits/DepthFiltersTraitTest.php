<?php

namespace FilterBuilder\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class DepthTest
 *
 * Unit tests for path depth-based filtering logic in the FilterBuilder.
 * These tests validate conditions for exact, greater than, and less than path depth.
 */
class DepthFiltersTraitTest extends TestCase
{
    /**
     * Test that depthEquals matches when the depth is exactly equal.
     *
     * @throws Exception
     */
    public function test_depth_equals()
    {
        $filter = new FilterBuilder();
        $filter->depthEquals(2);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('level1/level2/file.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that depthGt matches when the depth is greater than the specified value.
     *
     * @throws Exception
     */
    public function test_depth_gt()
    {
        $filter = new FilterBuilder();
        $filter->depthGt(1);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('a/b/c.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that depthGt does not match when the depth is not greater.
     *
     * @throws Exception
     */
    public function test_depth_gt_negative()
    {
        $filter = new FilterBuilder();
        $filter->depthGt(3);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('x/y/z.txt');

        $this->assertFalse($filter->matches($item));
    }

    /**
     * Test that depthLt matches when the depth is less than the specified value.
     *
     * @throws Exception
     */
    public function test_depth_lt()
    {
        $filter = new FilterBuilder();
        $filter->depthLt(2);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('shallow/file.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that depthLt does not match when the depth is not less.
     *
     * @throws Exception
     */
    public function test_depth_lt_negative()
    {
        $filter = new FilterBuilder();
        $filter->depthLt(1);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('one/two.txt');

        $this->assertFalse($filter->matches($item));
    }
}
