<?php

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FlysystemFilter;

use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class FlysystemFilterTest
 *
 * Unit tests for FlysystemFilter::filter() method.
 * Validates filtering functionality for various attribute types using FilterBuilder.
 */
class FlysystemFilterTest extends TestCase
{
    /**
     * Test filtering a DirectoryListing using a FilterBuilder with conditions.
     *
     * @throws Exception
     */
    public function test_filter_with_conditions()
    {
        $file1 = $this->createMock(FileAttributes::class);
        $file1->method('path')->willReturn('test/file1.txt');

        $file2 = $this->createMock(FileAttributes::class);
        $file2->method('path')->willReturn('test/file2.log');

        $directory = $this->createMock(DirectoryAttributes::class);
        $directory->method('path')->willReturn('test/subdir');

        $listing = new DirectoryListing([$file1, $file2, $directory]);

        $builder = new FilterBuilder();
        $builder->isFile()->and()->extensionEquals('txt');

        $filtered = FlysystemFilter::filter($listing, $builder);

        $items = $filtered->toArray();
        $this->assertCount(1, $items);
        $this->assertSame($file1, $items[0]);
    }

    /**
     * Test filtering a DirectoryListing without applying any conditions.
     *
     * @throws Exception
     */
    public function test_filter_without_conditions()
    {
        $file1 = $this->createMock(FileAttributes::class);
        $file1->method('path')->willReturn('test/file1.txt');

        $file2 = $this->createMock(FileAttributes::class);
        $file2->method('path')->willReturn('test/file2.log');

        $directory = $this->createMock(DirectoryAttributes::class);
        $directory->method('path')->willReturn('test/subdir');

        $listing = new DirectoryListing([$file1, $file2, $directory]);

        $builder = new FilterBuilder(); // no filters

        $filtered = FlysystemFilter::filter($listing, $builder);

        $this->assertCount(3, $filtered->toArray());
        $this->assertSame([$file1, $file2, $directory], $filtered->toArray());
    }

    /**
     * Test filtering when the DirectoryListing is empty.
     */
    public function test_filter_with_empty_directory_listing()
    {
        $listing = new DirectoryListing([]);

        $builder = new FilterBuilder();
        $builder->isFile();

        $filtered = FlysystemFilter::filter($listing, $builder);

        $this->assertCount(0, $filtered->toArray());
    }

    /**
     * Test filtering where only directories are expected.
     *
     * @throws Exception
     */
    public function test_filter_only_directories()
    {
        $file = $this->createMock(FileAttributes::class);
        $file->method('path')->willReturn('test/file.txt');

        $dir1 = $this->createMock(DirectoryAttributes::class);
        $dir1->method('path')->willReturn('test/dir1');

        $dir2 = $this->createMock(DirectoryAttributes::class);
        $dir2->method('path')->willReturn('test/dir2');

        $listing = new DirectoryListing([$file, $dir1, $dir2]);

        $builder = new FilterBuilder();
        $builder->isDirectory();

        $filtered = FlysystemFilter::filter($listing, $builder);

        $items = $filtered->toArray();
        $this->assertCount(2, $items);
        $this->assertContains($dir1, $items);
        $this->assertContains($dir2, $items);
    }

    /**
     * Test filtering files with a specific extension (e.g. .log).
     *
     * @throws Exception
     */
    public function test_filter_files_with_log_extension()
    {
        $file1 = $this->createMock(FileAttributes::class);
        $file1->method('path')->willReturn('log/file1.log');

        $file2 = $this->createMock(FileAttributes::class);
        $file2->method('path')->willReturn('log/file2.txt');

        $file3 = $this->createMock(FileAttributes::class);
        $file3->method('path')->willReturn('log/file3.log');

        $listing = new DirectoryListing([$file1, $file2, $file3]);

        $builder = new FilterBuilder();
        $builder->isFile()->and()->extensionEquals('log');

        $filtered = FlysystemFilter::filter($listing, $builder);

        $items = $filtered->toArray();
        $this->assertCount(2, $items);
        $this->assertContains($file1, $items);
        $this->assertContains($file3, $items);
    }

    /**
     * Test OR logic: isDirectory OR extension is 'txt'.
     *
     * @throws Exception
     */
    public function test_filter_dirs_or_txt_files()
    {
        $file1 = $this->createMock(FileAttributes::class);
        $file1->method('path')->willReturn('some/file1.txt');

        $file2 = $this->createMock(FileAttributes::class);
        $file2->method('path')->willReturn('some/file2.log');

        $dir = $this->createMock(DirectoryAttributes::class);
        $dir->method('path')->willReturn('some/dir');

        $listing = new DirectoryListing([$file1, $file2, $dir]);

        $builder = new FilterBuilder();
        $builder->isDirectory()->or()->extensionEquals('txt');

        $filtered = FlysystemFilter::filter($listing, $builder);

        $items = $filtered->toArray();
        $this->assertCount(2, $items);
        $this->assertContains($file1, $items);
        $this->assertContains($dir, $items);
    }

    /**
     * Test filtering where one of the items is not a StorageAttributes object.
     *
     * @throws Exception
     */
    public function test_filter_with_non_storage_attribute_objects()
    {
        $invalidObject = new stdClass();

        $file = $this->createMock(FileAttributes::class);
        $file->method('path')->willReturn('test/file.txt');

        $listing = new DirectoryListing([$file, $invalidObject]);

        $builder = new FilterBuilder();
        $builder->isFile();

        $filtered = FlysystemFilter::filter($listing, $builder);

        $items = $filtered->toArray();
        $this->assertCount(1, $items);
        $this->assertSame($file, $items[0]);
    }

    /**
     * Test filtering with case-insensitive extension comparison.
     *
     * @throws Exception
     */
    public function test_filter_with_case_insensitive_extension()
    {
        $file1 = $this->createMock(FileAttributes::class);
        $file1->method('path')->willReturn('doc/file1.TXT');

        $file2 = $this->createMock(FileAttributes::class);
        $file2->method('path')->willReturn('doc/file2.txt');

        $file3 = $this->createMock(FileAttributes::class);
        $file3->method('path')->willReturn('doc/file3.log');

        $listing = new DirectoryListing([$file1, $file2, $file3]);

        $builder = new FilterBuilder();
        $builder->isFile()->and()->extensionEquals('txt');

        $filtered = FlysystemFilter::filter($listing, $builder);

        $items = $filtered->toArray();
        $this->assertCount(2, $items);
        $this->assertContains($file1, $items);
        $this->assertContains($file2, $items);
    }
}
