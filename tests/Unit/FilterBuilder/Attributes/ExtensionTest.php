<?php

namespace FilterBuilder\Attributes;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class ExtensionTest
 *
 * Unit tests for extension-related filters in the FilterBuilder.
 * These tests ensure that file extensions are correctly matched, excluded,
 * and checked for substring presence in case-insensitive comparisons.
 */
class ExtensionTest extends TestCase
{
    /**
     * Test filtering by exact extension match (case-insensitive).
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
     * Test filtering by extension not matching the given one.
     *
     * @throws Exception
     */
    public function test_extension_not_equals()
    {
        $filter = new FilterBuilder();
        $filter->extensionNotEquals(['jpg', 'png']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/documents/file.txt');

        $this->assertTrue($filter->matches($item));
    }

    /**
     * Test filtering where extension contains a specific substring (case-insensitive).
     *
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
     * Test filtering where extension does not contain the specified substring.
     *
     * @throws Exception
     */
    public function test_extension_not_contains()
    {
        $filter = new FilterBuilder();
        $filter->extensionNotContains(['pdf', 'doc']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('/some/file.txt');

        $this->assertTrue($filter->matches($item));
    }
}
