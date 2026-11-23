<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Closure;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SizeFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, StorageAttributes, bool}>
     */
    public static function cases(): iterable
    {
        yield 'equals bytes' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeEquals(1024), Items::file('a', 1024), true];
        yield 'equals unit' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeEquals('1K'), Items::file('a', 1024), true];
        yield 'equals no match' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeEquals('1K'), Items::file('a', 1000), false];
        yield 'not equals' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeNotEquals(5), Items::file('a', 6), true];
        yield 'not equals same' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeNotEquals(5), Items::file('a', 5), false];
        yield 'gt' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeGt('1M'), Items::file('a', 1024 ** 2 + 1), true];
        yield 'gt is exclusive' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeGt('1M'), Items::file('a', 1024 ** 2), false];
        yield 'gte is inclusive' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeGte('1M'), Items::file('a', 1024 ** 2), true];
        yield 'lt' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeLt('1G'), Items::file('a', 10), true];
        yield 'lt is exclusive' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeLt(10), Items::file('a', 10), false];
        yield 'lte is inclusive' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeLte(10), Items::file('a', 10), true];
        yield 'between inclusive low' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeBetween('500K', '5M'), Items::file('a', 500 * 1024), true];
        yield 'between inclusive high' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeBetween('500K', '5M'), Items::file('a', 5 * 1024 ** 2), true];
        yield 'between outside' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeBetween('500K', '5M'), Items::file('a', 10), false];
        yield 'decimal unit' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeLt('1.5M'), Items::file('a', 1024 ** 2), true];
        yield 'KB / MB / GiB units' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeBetween('10KB', '2 GiB'), Items::file('a', 1024 ** 2), true];
        yield 'empty file' => [static fn(FilterBuilder $f): FilterBuilder => $f->isEmptyFile(), Items::file('a', 0), true];
        yield 'non-empty file' => [static fn(FilterBuilder $f): FilterBuilder => $f->isEmptyFile(), Items::file('a', 1), false];
        // Bug fixed in v4: `null < 1024` is true in PHP, so unknown sizes used to match.
        yield 'unknown size never matches lt' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeLt('1K'), Items::file('a', null), false];
        yield 'unknown size never matches lte' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeLte('1K'), Items::file('a', null), false];
        yield 'unknown size never matches not equals' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeNotEquals(5), Items::file('a', null), false];
        yield 'unknown size never matches empty file' => [static fn(FilterBuilder $f): FilterBuilder => $f->isEmptyFile(), Items::file('a', null), false];
        yield 'directories never match' => [static fn(FilterBuilder $f): FilterBuilder => $f->sizeLt('1G'), Items::dir('docs'), false];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function size_filters(Closure $build, StorageAttributes $item, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches($item));
    }

    /**
     * @return iterable<string, array{int|string}>
     */
    public static function invalidSizes(): iterable
    {
        yield 'unknown unit' => ['10X'];
        yield 'garbage' => ['big'];
        yield 'negative number' => [-1];
        yield 'negative string' => ['-5K'];
        yield 'empty' => [''];
    }

    #[Test]
    #[DataProvider('invalidSizes')]
    public function invalid_sizes_are_rejected(int|string $size): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FilterBuilder())->sizeGt($size);
    }

    #[Test]
    public function between_rejects_inverted_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('larger than the maximum');

        (new FilterBuilder())->sizeBetween('5M', '1M');
    }
}
