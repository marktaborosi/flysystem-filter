<?php
namespace Marktaborosi\FlysystemFilter\Tests\Unit;

use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\DirectoryAttributes;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class FlysystemFilterTest extends TestCase
{
    /**
     * Test filtering a DirectoryListing using a FilterBuilder.
     * @throws Exception
     */
    public function test_filter_with_conditions()
    {
        // Mock DirectoryListing items
        $file1 = $this->createMock(FileAttributes::class);
        $file1->method('path')->willReturn('test/file1.txt');

        $file2 = $this->createMock(FileAttributes::class);
        $file2->method('path')->willReturn('test/file2.log');

        $directory = $this->createMock(DirectoryAttributes::class);
        $directory->method('path')->willReturn('test/subdir');

        // Create a DirectoryListing mock
        $directoryListing = new DirectoryListing([$file1, $file2, $directory]);

        // Create a FilterBuilder instance and set conditions
        $builder = new FilterBuilder();
        $builder->isFile()->and()->extensionEquals('txt');

        // Apply the filter
        $filteredListing = FlysystemFilter::filter($directoryListing, $builder);

        // Assert the filtered result contains only file1
        $filteredItems = $filteredListing->toArray();
        $this->assertCount(1, $filteredItems);
        $this->assertSame($file1, $filteredItems[0]);
    }

    /**
     * Test filtering a DirectoryListing with no filters applied.
     * @throws Exception
     */
    public function test_filter_without_conditions()
    {
        // Mock DirectoryListing items
        $file1 = $this->createMock(FileAttributes::class);
        $file1->method('path')->willReturn('test/file1.txt');

        $file2 = $this->createMock(FileAttributes::class);
        $file2->method('path')->willReturn('test/file2.log');

        $directory = $this->createMock(DirectoryAttributes::class);
        $directory->method('path')->willReturn('test/subdir');

        // Create a DirectoryListing mock
        $directoryListing = new DirectoryListing([$file1, $file2, $directory]);

        // Create an empty FilterBuilder instance
        $builder = new FilterBuilder();

        // Apply the filter
        $filteredListing = FlysystemFilter::filter($directoryListing, $builder);

        // Assert the filtered result contains all items
        $filteredItems = $filteredListing->toArray();
        $this->assertCount(3, $filteredItems);
        $this->assertSame([$file1, $file2, $directory], $filteredItems);
    }

    /**
     * Test filtering an empty DirectoryListing.
     */
    public function test_filter_with_empty_directory_listing()
    {
        // Create an empty DirectoryListing mock
        $directoryListing = new DirectoryListing([]);

        // Create a FilterBuilder instance with any condition
        $builder = new FilterBuilder();
        $builder->isFile();

        // Apply the filter
        $filteredListing = FlysystemFilter::filter($directoryListing, $builder);

        // Assert the filtered result is empty
        $this->assertCount(0, $filteredListing->toArray());
    }
}
