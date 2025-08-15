<?php

namespace FilterBuilder\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class FilenameTest
 *
 * Unit tests for filename-related filters in the FilterBuilder.
 * These tests ensure that filename-based conditions (excluding extensions)
 * are properly evaluated using exact match, negation, substring presence, and regex.
 */
class FilenameFiltersTraitTest extends TestCase
{
    /**
     * Test that filenameEquals matches when the filename (without extension) is exactly equal.
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
     * Test that filenameNotEquals matches when the filename (without extension) is not equal.
     *
     * @throws Exception
     */
    public function test_filename_not_equals()
    {
        $filter = new FilterBuilder();
        $filter->filenameNotEquals('document');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/files/invoice.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that filenameContains matches when the filename includes a given substring.
     *
     * @throws Exception
     */
    public function test_filename_contains()
    {
        $filter = new FilterBuilder();
        $filter->filenameContains(['report']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/archive/weekly_report_2024.pdf');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that filenameNotContains matches when the filename does not include any of the given substrings.
     *
     * @throws Exception
     */
    public function test_filename_not_contains()
    {
        $filter = new FilterBuilder();
        $filter->filenameNotContains(['temp', 'backup']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/content/article_final.docx');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that filenameMatchesRegex correctly matches a filename against a given pattern.
     *
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

    /**
     * Test that filenameMatchesRegex does not match when pattern fails.
     *
     * @throws Exception
     */
    public function test_filename_matches_regex_negative()
    {
        $filter = new FilterBuilder();
        $filter->filenameMatchesRegex('/^report_\d+$/');

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/archive/summary_final.txt');

        $this->assertFalse($filter->matches($item));
    }

    /**
     * Test that filenameStartsWith matches when the filename starts with a given prefix.
     *
     * @throws Exception
     */
    public function test_filename_starts_with()
    {
        $filter = new FilterBuilder();
        $filter->filenameStartsWith(['report', 'invoice']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/docs/report2024.pdf');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that filenameStartsWith does not match when no prefix matches.
     *
     * @throws Exception
     */
    public function test_filename_starts_with_negative()
    {
        $filter = new FilterBuilder();
        $filter->filenameStartsWith(['log', 'debug']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/logs/error2024.txt');

        $this->assertFalse($filter->matches($item));
    }

    /**
     * Test that filenameEndsWith matches when the filename ends with a given suffix.
     *
     * @throws Exception
     */
    public function test_filename_ends_with()
    {
        $filter = new FilterBuilder();
        $filter->filenameEndsWith(['2024', '_final']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/articles/article_final.docx');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that filenameEndsWith does not match when no suffix matches.
     *
     * @throws Exception
     */
    public function test_filename_ends_with_negative()
    {
        $filter = new FilterBuilder();
        $filter->filenameEndsWith(['draft', 'test']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/content/article_final.pdf');

        $this->assertFalse($filter->matches($item));
    }
}
