<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Closure;
use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\MimeTypeDetector;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MimeTypeFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, StorageAttributes, bool}>
     */
    public static function cases(): iterable
    {
        yield 'equals' => [static fn(FilterBuilder $f) => $f->mimeTypeEquals('image/png'), Items::file('a.png'), true];
        yield 'equals list' => [static fn(FilterBuilder $f) => $f->mimeTypeEquals(['application/pdf', 'text/plain']), Items::file('a.txt'), true];
        yield 'equals is case-insensitive' => [static fn(FilterBuilder $f) => $f->mimeTypeEquals('IMAGE/PNG'), Items::file('a.png'), true];
        yield 'equals no match' => [static fn(FilterBuilder $f) => $f->mimeTypeEquals('image/png'), Items::file('a.jpg'), false];
        yield 'equals unknown' => [static fn(FilterBuilder $f) => $f->mimeTypeEquals('image/png'), Items::file('a.unknownext'), false];
        yield 'not equals' => [static fn(FilterBuilder $f) => $f->mimeTypeNotEquals('application/zip'), Items::file('a.png'), true];
        yield 'not equals excluded' => [static fn(FilterBuilder $f) => $f->mimeTypeNotEquals('application/zip'), Items::file('a.zip'), false];
        yield 'not equals unknown matches' => [static fn(FilterBuilder $f) => $f->mimeTypeNotEquals('application/zip'), Items::file('a.unknownext'), true];
        yield 'contains' => [static fn(FilterBuilder $f) => $f->mimeTypeContains(['image', 'video']), Items::file('a.mp4'), true];
        yield 'not contains' => [static fn(FilterBuilder $f) => $f->mimeTypeNotContains('video'), Items::file('a.png'), true];
        yield 'not contains excluded' => [static fn(FilterBuilder $f) => $f->mimeTypeNotContains('video'), Items::file('a.mp4'), false];
        yield 'glob' => [static fn(FilterBuilder $f) => $f->mimeTypeMatches('image/*'), Items::file('a.webp'), true];
        yield 'glob alternatives' => [static fn(FilterBuilder $f) => $f->mimeTypeMatches('application/{pdf,zip}'), Items::file('a.zip'), true];
        yield 'is image' => [static fn(FilterBuilder $f) => $f->isImage(), Items::file('a.JPG'), true];
        yield 'is image no' => [static fn(FilterBuilder $f) => $f->isImage(), Items::file('a.txt'), false];
        yield 'is video' => [static fn(FilterBuilder $f) => $f->isVideo(), Items::file('a.mov'), true];
        yield 'is audio' => [static fn(FilterBuilder $f) => $f->isAudio(), Items::file('a.mp3'), true];
        yield 'is text' => [static fn(FilterBuilder $f) => $f->isText(), Items::file('a.csv'), true];
        yield 'adapter metadata wins over extension' => [static fn(FilterBuilder $f) => $f->isImage(), Items::file('upload.bin', mimeType: 'image/png'), true];
        yield 'directories have no MIME type' => [static fn(FilterBuilder $f) => $f->mimeTypeContains('image'), Items::dir('photos.jpg'), false];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function mime_type_filters(Closure $build, StorageAttributes $item, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches($item));
    }

    #[Test]
    public function custom_detector_can_be_injected(): void
    {
        $detector = new class implements MimeTypeDetector {
            public function detectMimeType(string $path, $contents): string
            {
                return 'application/x-custom';
            }

            public function detectMimeTypeFromBuffer(string $contents): string
            {
                return 'application/x-custom';
            }

            public function detectMimeTypeFromPath(string $path): string
            {
                return 'application/x-custom';
            }

            public function detectMimeTypeFromFile(string $path): string
            {
                return 'application/x-custom';
            }
        };

        $filter = FilterBuilder::create($detector)->mimeTypeEquals('application/x-custom');

        self::assertTrue($filter->matches(Items::file('a.txt')));
        // the detector is inherited by nested groups
        self::assertTrue(FilterBuilder::create($detector)->where(static fn(FilterBuilder $q) => $q->mimeTypeEquals('application/x-custom'))->matches(Items::file('a.txt')));
    }
}
