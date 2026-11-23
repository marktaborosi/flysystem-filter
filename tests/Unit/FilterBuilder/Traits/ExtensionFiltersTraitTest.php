<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Closure;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExtensionFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, StorageAttributes, bool}>
     */
    public static function cases(): iterable
    {
        yield 'equals' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals('jpg'), Items::file('a.jpg'), true];
        yield 'equals is case-insensitive by default' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals('jpg'), Items::file('IMG.JPG'), true];
        yield 'equals case-sensitive on request' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals('jpg', ignoreCase: false), Items::file('IMG.JPG'), false];
        yield 'equals list' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals(['txt', 'log']), Items::file('a.log'), true];
        yield 'equals strips leading dot' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals('.txt'), Items::file('a.txt'), true];
        yield 'equals uses last extension' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals('gz'), Items::file('a.tar.gz'), true];
        yield 'equals no extension (no deprecation)' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals('txt'), Items::file('docs/README'), false];
        yield 'equals ignores directories' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionEquals('d'), Items::dir('conf.d'), false];
        yield 'not equals' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionNotEquals(['exe', 'bak']), Items::file('a.txt'), true];
        yield 'not equals excluded' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionNotEquals(['exe', 'bak']), Items::file('a.BAK'), false];
        yield 'not equals without extension' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionNotEquals('txt'), Items::file('README'), true];
        yield 'contains' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionContains('ml'), Items::file('index.xhtml'), true];
        yield 'contains no extension' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionContains('ml'), Items::file('Makefile'), false];
        yield 'not contains' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionNotContains('ml'), Items::file('a.json'), true];
        yield 'not contains excluded' => [static fn(FilterBuilder $f): FilterBuilder => $f->extensionNotContains(['ml', 'js']), Items::file('a.json'), false];
        yield 'has extension' => [static fn(FilterBuilder $f): FilterBuilder => $f->hasExtension(), Items::file('a.json'), true];
        yield 'has no extension' => [static fn(FilterBuilder $f): FilterBuilder => $f->hasExtension(), Items::file('LICENSE'), false];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function extension_filters(Closure $build, StorageAttributes $item, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches($item));
    }
}
