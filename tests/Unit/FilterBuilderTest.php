<?php
namespace Marktaborosi\FlysystemFilter\Tests\Unit;

use Carbon\Carbon;
use LogicException;
use PHPUnit\Framework\MockObject\Exception;
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
     * @throws Exception
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
     * @throws Exception
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
     * Test filtering by exact filename match (without extension).
     *
     * @throws Exception
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
     * @throws Exception
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
     * Test filtering by file size between two values by bytes.
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
     * Test filtering by file size between two values by kilobytes.
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
     * Test filtering by file size between two values by megabytes.
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
     * Test filtering by file size between two values by gigabytes.
     *
     * @throws Exception
     */
    public function test_size_between_with_gigabytes()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween('1G', '2G');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1073741824);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size between two values by terrabytes.
     *
     * @throws Exception
     */
    public function test_size_between_with_terrabytes()
    {
        $filter = new FilterBuilder();
        $filter->sizeBetween('1T', '2T');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1649267441664);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering by file size with bad unit values
     * @throws Exception
     */
    public function test_size_between_with_bad_unit_throws_logic_exception() {
        $this->expectException(LogicException::class);

        $filter = new FilterBuilder();
        $filter->sizeBetween('1F','2F');

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willThrowException(new LogicException());
    }


    /**
     * Test filtering by last modified date between two timestamps.
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
     * Test grouping of conditions.
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
     * Test combining multiple conditions.
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
     * OR operator test
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
     * Path not equals
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
     * Basename not contains
     * @throws Exception
     */
    public function test_basename_not_contains()
    {
        $filter = new FilterBuilder();
        $filter->basenameNotContains(['temp']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/files/final_report.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Group end with start throws
     */
    public function test_group_end_without_start_throws()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mismatched group_end');

        $filter = new FilterBuilder();
        $filter->group_end();
    }

    /**
     * Unclosed group throws
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

        $filter->matches($item); // group_end hiányzik
    }

    /**
     * Size equals with string format
     * @throws Exception
     */
    public function test_size_equals_with_string_format()
    {
        $filter = new FilterBuilder();
        $filter->sizeEquals('1M'); // 1 MB

        $item = $this->createMock(FileAttributes::class);
        $item->method('fileSize')->willReturn(1024 * 1024);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Metadata equals
     * @throws Exception
     */
    public function test_metadata_equals()
    {
        $filter = new FilterBuilder();
        $filter->metadataEquals('type', 'invoice');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('extraMetadata')->willReturn(['type' => 'invoice']);

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Visibility filters
     * @throws Exception
     */
    public function test_visibility_filters()
    {
        $publicFilter = (new FilterBuilder())->isPublic();
        $privateFilter = (new FilterBuilder())->isPrivate();

        $publicItem = $this->createMock(StorageAttributes::class);
        $publicItem->method('visibility')->willReturn('public');

        $privateItem = $this->createMock(StorageAttributes::class);
        $privateItem->method('visibility')->willReturn('private');

        $this->assertTrue($publicFilter->matches($publicItem));
        $this->assertTrue($privateFilter->matches($privateItem));
    }

    /**
     * Extension contains
     * @throws Exception
     */
    public function test_extension_contains()
    {
        $filter = new FilterBuilder();
        $filter->extensionContains('x');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/file.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Filename matches regex
     * @throws Exception
     */
    public function test_filename_matches_regex()
    {
        $filter = new FilterBuilder();
        $filter->filenameMatchesRegex('/^report_\d+$/');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/archive/report_123.txt');

        $this->assertTrue($filter->matches($item));
    }

}
