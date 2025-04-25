<?php

namespace FilterBuilder\Logic;

use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use LogicException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class LogicTest
 *
 * Unit tests for advanced condition logic in the FilterBuilder.
 * Covers logical grouping, AND/OR chaining, and validation of grouping rules.
 */
class LogicTest extends TestCase
{
    /**
     * Test that grouped conditions with AND/OR logic evaluate correctly.
     *
     * @throws Exception
     */
    public function test_group_conditions()
    {
        $filter = new FilterBuilder();
        $filter
            ->pathContains('path')
            ->and()
            ->group_start()
            ->extensionEquals('txt')
            ->or()
            ->extensionEquals('md')
            ->group_end();

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/path/file.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that calling group_end() without a prior group_start() throws a LogicException.
     */
    public function test_group_end_without_start_throws()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mismatched group_end');

        $filter = new FilterBuilder();
        $filter->group_end();
    }

    /**
     * Test that an unclosed group (missing group_end) throws a LogicException.
     *
     * @throws Exception
     */
    public function test_unclosed_group_throws()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unbalanced groups');

        $filter = new FilterBuilder();
        $filter->group_start()->extensionEquals('txt');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/file.txt');

        $filter->matches($item);
    }

    /**
     * Test combining multiple conditions with logical AND operators.
     *
     * @throws Exception
     */
    public function test_combined_conditions()
    {
        $filter = new FilterBuilder();
        $filter->isFile()->and()->extensionEquals('txt')->and()->sizeGt(100);

        $item = $this->createMock(FileAttributes::class);
        $item->method('path')->willReturn('/some/path/file.txt');
        $item->method('fileSize')->willReturn(150);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test logical OR operator between two different filters.
     *
     * @throws Exception
     */
    public function test_or_operator_works()
    {
        $filter = new FilterBuilder();
        $filter->filenameEquals('readme')->or()->extensionEquals('md');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/doc/readme.md');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that an empty filter builder (no conditions) always returns true.
     *
     * @throws Exception
     */
    public function test_empty_filter_returns_true()
    {
        $filter = new FilterBuilder();

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/any/path');

        $this->assertTrue($filter->matches($item), 'Expected matches() to return true for empty filter');
    }
}
