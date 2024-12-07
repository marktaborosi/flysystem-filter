<?php
namespace Marktaborosi\FlysystemFilter\Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use League\Flysystem\StorageAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Class FilterBuilderTest
 *
 * Tests for the FilterBuilder class functionality.
 */
class FilterBuilderTest extends TestCase
{
    /**
     * Test filtering only files.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_is_file()
    {
        $filter = new FilterBuilder();
        $filter->isFile();

        $file = $this->createMock(FileAttributes::class);
        $directory = $this->createMock(DirectoryAttributes::class);

        $this->assertTrue($filter->matches($file));
        $this->assertFalse($filter->matches($directory));
    }

    /**
     * Test filtering only directories.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_is_directory()
    {
        $filter = new FilterBuilder();
        $filter->isDirectory();

        $file = $this->createMock(FileAttributes::class);
        $directory = $this->createMock(DirectoryAttributes::class);

        $this->assertTrue($filter->matches($directory));
        $this->assertFalse($filter->matches($file));
    }

    /**
     * Test filtering by exact path match.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
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
     * @throws \PHPUnit\Framework\MockObject\Exception
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
     * Test filtering by exact filename match (without extension).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_filename_equals()
    {
        $filter = new FilterBuilder();
        $filter->filenameEquals('file');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/path/file.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by exact extension match.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_extension_equals()
    {
        $filter = new FilterBuilder();
        $filter->extensionEquals('txt');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/path/file.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size between two values.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
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
     * Test filtering by last modified date between two timestamps.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
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
     * Test grouping of conditions.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
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
     * Test combining multiple conditions.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
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
}
