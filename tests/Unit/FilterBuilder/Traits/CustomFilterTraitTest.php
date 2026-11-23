<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CustomFilterTraitTest extends TestCase
{
    #[Test]
    public function custom_predicate(): void
    {
        $filter = (new FilterBuilder())->custom(static fn(StorageAttributes $item): bool => \strlen($item->path()) > 10, 'long path');

        self::assertTrue($filter->matches(Items::file('some/long/path.txt')));
        self::assertFalse($filter->matches(Items::file('short.txt')));
        self::assertSame('long path', $filter->describe());
    }

    #[Test]
    public function any_callable_is_accepted(): void
    {
        $filter = (new FilterBuilder())->custom([self::class, 'isTxt']);

        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertSame('custom callback', $filter->describe());
    }

    public static function isTxt(StorageAttributes $item): bool
    {
        return str_ends_with($item->path(), '.txt');
    }
}
