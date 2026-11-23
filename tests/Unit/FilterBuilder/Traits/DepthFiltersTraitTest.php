<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Closure;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DepthFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, string, bool}>
     */
    public static function cases(): iterable
    {
        yield 'root file depth 0' => [static fn(FilterBuilder $f) => $f->depthEquals(0), 'file.txt', true];
        yield 'nested depth 1' => [static fn(FilterBuilder $f) => $f->depthEquals(1), 'documents/file.txt', true];
        yield 'leading slash ignored' => [static fn(FilterBuilder $f) => $f->depthEquals(3), '/a/b/c/file.txt', true];
        yield 'trailing slash ignored' => [static fn(FilterBuilder $f) => $f->depthEquals(1), 'a/b/', true];
        yield 'gt' => [static fn(FilterBuilder $f) => $f->depthGt(1), 'a/b/c.txt', true];
        yield 'gt exclusive' => [static fn(FilterBuilder $f) => $f->depthGt(2), 'a/b/c.txt', false];
        yield 'gte' => [static fn(FilterBuilder $f) => $f->depthGte(2), 'a/b/c.txt', true];
        yield 'lt' => [static fn(FilterBuilder $f) => $f->depthLt(1), 'c.txt', true];
        yield 'lt exclusive' => [static fn(FilterBuilder $f) => $f->depthLt(1), 'a/c.txt', false];
        yield 'lte' => [static fn(FilterBuilder $f) => $f->depthLte(1), 'a/c.txt', true];
        yield 'between' => [static fn(FilterBuilder $f) => $f->depthBetween(1, 2), 'a/b/c.txt', true];
        yield 'between outside' => [static fn(FilterBuilder $f) => $f->depthBetween(1, 2), 'a/b/c/d.txt', false];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function depth_filters(Closure $build, string $path, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches(Items::file($path)));
    }

    #[Test]
    public function between_rejects_inverted_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FilterBuilder())->depthBetween(3, 1);
    }
}
