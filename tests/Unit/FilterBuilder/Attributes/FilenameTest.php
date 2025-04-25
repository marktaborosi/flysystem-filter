<?php

namespace FilterBuilder\Attributes;

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
class FilenameTest extends TestCase
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
}
