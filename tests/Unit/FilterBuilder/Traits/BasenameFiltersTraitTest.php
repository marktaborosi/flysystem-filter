<?php

namespace FilterBuilder\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class BasenameTest
 *
 * Unit tests for basename-related conditions in the FilterBuilder.
 * These tests validate filtering logic based on the basename (filename with extension)
 * of file paths provided by Flysystem's StorageAttributes.
 */
class BasenameFiltersTraitTest extends TestCase
{
    /**
     * Test that basenameEquals matches when the file's basename exactly matches one of the specified values.
     *
     * @throws Exception
     */
    public function test_basename_equals()
    {
        $filter = new FilterBuilder();
        $filter->basenameEquals(['final_report.txt']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/files/final_report.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that basenameNotEquals matches when the file's basename does not match any of the specified values.
     *
     * @throws Exception
     */
    public function test_basename_not_equals()
    {
        $filter = new FilterBuilder();
        $filter->basenameNotEquals(['draft.doc']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/documents/final.doc');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that basenameContains matches when the file's basename contains the specified substring(s).
     *
     * @throws Exception
     */
    public function test_basename_contains()
    {
        $filter = new FilterBuilder();
        $filter->basenameContains(['report']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/files/financial_report_2024.csv');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that basenameNotContains matches when the file's basename does not contain any of the specified substrings.
     *
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
     * Test that basenameStartsWith matches when the basename starts with a given prefix.
     *
     * @throws Exception
     */
    public function test_basename_starts_with()
    {
        $filter = new FilterBuilder();
        $filter->basenameStartsWith(['report_', 'invoice_']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/archive/report_2024_final.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that basenameStartsWith does not match when the basename does not start with any of the specified prefixes.
     *
     * @throws Exception
     */
    public function test_basename_starts_with_negative()
    {
        $filter = new FilterBuilder();
        $filter->basenameStartsWith(['log_', 'debug_']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/archive/report_2024_final.txt');

        $this->assertFalse($filter->matches($item));
    }

    /**
     * Test that basenameEndsWith matches when the basename ends with a given suffix.
     *
     * @throws Exception
     */
    public function test_basename_ends_with()
    {
        $filter = new FilterBuilder();
        $filter->basenameEndsWith(['_final.txt', '_draft.csv']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/documents/summary_final.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test that basenameEndsWith does not match when the basename does not end with any of the specified suffixes.
     *
     * @throws Exception
     */
    public function test_basename_ends_with_negative()
    {
        $filter = new FilterBuilder();
        $filter->basenameEndsWith(['.json', '_temp.doc']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/documents/summary_final.txt');

        $this->assertFalse($filter->matches($item));
    }



}
