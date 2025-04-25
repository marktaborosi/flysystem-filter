<?php

namespace FilterBuilder\Attributes;

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
class BasenameTest extends TestCase
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
}
