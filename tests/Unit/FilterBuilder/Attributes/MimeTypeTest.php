<?php

namespace FilterBuilder\Attributes;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Class MimeTypeTest
 *
 * Unit tests for mime type related filtering in the FilterBuilder.
 * Uses reflection to test MIME type detection based on file path,
 * simulating real scenarios without reading file contents.
 */
class MimeTypeTest extends TestCase
{
    /**
     * Test that mimeTypeEquals correctly matches a file path's MIME type
     * when the MIME type equals one of the given values.
     *
     * @return void
     * @throws Exception|ReflectionException
     */
    public function test_mime_type_equals()
    {
        $filter = new FilterBuilder();
        $filter->mimeTypeEquals(['image/png', 'image/jpeg']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('path/to/image.png');

        $reflection = new ReflectionClass($filter);
        $method = $reflection->getMethod('getMimeTypeFromPath');

        $mimeType = $method->invoke($filter, $item->path());

        $this->assertTrue(in_array($mimeType, ['image/png', 'image/jpeg'], true));
    }

    /**
     * Test that mimeTypeNotEquals correctly excludes a file path's MIME type
     * if it matches one of the given types.
     *
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public function test_mime_type_not_equals()
    {
        $filter = new FilterBuilder();
        $filter->mimeTypeNotEquals(['image/png', 'image/jpeg']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('path/to/document.pdf');

        $reflection = new ReflectionClass($filter);
        $method = $reflection->getMethod('getMimeTypeFromPath');

        $mimeType = $method->invoke($filter, $item->path());

        $this->assertFalse(in_array($mimeType, ['image/png', 'image/jpeg'], true));
    }

    /**
     * Test that mimeTypeContains correctly matches MIME types
     * that contain any of the specified substrings.
     *
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public function test_mime_type_contains()
    {
        $filter = new FilterBuilder();
        $filter->mimeTypeContains(['image', 'video']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('path/to/photo.jpg');

        $reflection = new ReflectionClass($filter);
        $method = $reflection->getMethod('getMimeTypeFromPath');

        $mimeType = $method->invoke($filter, $item->path());

        $this->assertTrue(str_contains($mimeType, 'image') || str_contains($mimeType, 'video'));
    }

    /**
     * Test that mimeTypeNotContains correctly excludes MIME types
     * that contain any of the specified substrings.
     *
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public function test_mime_type_not_contains()
    {
        $filter = new FilterBuilder();
        $filter->mimeTypeNotContains(['image', 'video']);

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('path/to/data.json');

        $reflection = new ReflectionClass($filter);
        $method = $reflection->getMethod('getMimeTypeFromPath');

        $mimeType = $method->invoke($filter, $item->path());

        $this->assertFalse(str_contains($mimeType, 'image') || str_contains($mimeType, 'video'));
    }
}
