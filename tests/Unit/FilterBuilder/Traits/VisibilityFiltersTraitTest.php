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

final class VisibilityFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, StorageAttributes, bool}>
     */
    public static function cases(): iterable
    {
        yield 'public' => [static fn(FilterBuilder $f): FilterBuilder => $f->isPublic(), Items::file('a', visibility: 'public'), true];
        yield 'public no' => [static fn(FilterBuilder $f): FilterBuilder => $f->isPublic(), Items::file('a', visibility: 'private'), false];
        yield 'private' => [static fn(FilterBuilder $f): FilterBuilder => $f->isPrivate(), Items::dir('a', visibility: 'private'), true];
        yield 'unknown is neither' => [static fn(FilterBuilder $f): FilterBuilder => $f->isPublic(), Items::file('a', visibility: null), false];
        yield 'equals custom' => [static fn(FilterBuilder $f): FilterBuilder => $f->visibilityEquals(['team', 'admin']), Items::file('a', visibility: 'admin'), true];
        yield 'not equals' => [static fn(FilterBuilder $f): FilterBuilder => $f->visibilityNotEquals('private'), Items::file('a', visibility: 'public'), true];
        yield 'not equals excluded' => [static fn(FilterBuilder $f): FilterBuilder => $f->visibilityNotEquals('private'), Items::file('a', visibility: 'private'), false];
        yield 'not equals requires known visibility' => [static fn(FilterBuilder $f): FilterBuilder => $f->visibilityNotEquals('private'), Items::file('a', visibility: null), false];
        yield 'contains' => [static fn(FilterBuilder $f): FilterBuilder => $f->visibilityContains('public'), Items::file('a', visibility: 'user-public'), true];
        yield 'contains unknown' => [static fn(FilterBuilder $f): FilterBuilder => $f->visibilityContains('public'), Items::file('a', visibility: null), false];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function visibility_filters(Closure $build, StorageAttributes $item, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches($item));
    }
}
