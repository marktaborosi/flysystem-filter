<?php

namespace FilterBuilder\Attributes;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class FileDirectoryTest
 *
 * Unit tests for file vs. directory filters in FilterBuilder.
 * These tests ensure that `isFile()` and `isDirectory()` correctly differentiate
 * between FileAttributes and DirectoryAttributes provided by Flysystem.
 */
class FileDirectoryTest extends TestCase
{
    /**
     * Test that the filter correctly matches only files using `isFile()`.
     *
     * @throws Exception
     */
    public function test_is_file()
    {
        $filter = new FilterBuilder();
        $filter->isFile();

        $file = $this->createMock(FileAttributes::class);
        $directory = $this->createMock(DirectoryAttributes::class);

        $this->assertTrue($filter->matches($file), 'Expected file to match isFile() filter.');
        $this->assertFalse($filter->matches($directory), 'Expected directory to not match isFile() filter.');
    }

    /**
     * Test that the filter correctly matches only directories using `isDirectory()`.
     *
     * @throws Exception
     */
    public function test_is_directory()
    {
        $filter = new FilterBuilder();
        $filter->isDirectory();

        $file = $this->createMock(FileAttributes::class);
        $directory = $this->createMock(DirectoryAttributes::class);

        $this->assertTrue($filter->matches($directory), 'Expected directory to match isDirectory() filter.');
        $this->assertFalse($filter->matches($file), 'Expected file to not match isDirectory() filter.');
    }

    /**
     * Test that a filter with no conditions matches all items by default.
     *
     * @throws Exception
     */
    public function test_no_conditions_returns_true()
    {
        $filter = new FilterBuilder();

        $file = $this->createMock(FileAttributes::class);
        $directory = $this->createMock(DirectoryAttributes::class);

        $this->assertTrue($filter->matches($file), 'Expected file to match when no conditions are set.');
        $this->assertTrue($filter->matches($directory), 'Expected directory to match when no conditions are set.');
    }
}
