<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder;

use BadMethodCallException;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MacroTest extends TestCase
{
    protected function tearDown(): void
    {
        FilterBuilder::flushMacros();
    }

    #[Test]
    public function macros_add_reusable_named_filters(): void
    {
        FilterBuilder::macro('isLog', static fn(FilterBuilder $q): FilterBuilder => $q->where(
            static fn(FilterBuilder $g): FilterBuilder => $g->extensionEquals(['log', 'gz'])->and()->pathStartsWith('logs/'),
        ));

        self::assertTrue(FilterBuilder::hasMacro('isLog'));

        $filter = FilterBuilder::create();
        $filter->isLog();
        $filter->or()->isImage();

        self::assertTrue($filter->matches(Items::file('logs/a.log')));
        self::assertTrue($filter->matches(Items::file('a.png')));
        self::assertFalse($filter->matches(Items::file('a.log')));
    }

    #[Test]
    public function macros_receive_arguments_and_may_return_nothing(): void
    {
        FilterBuilder::macro('olderThanDays', static function (FilterBuilder $q, int $days): void {
            $q->lastModifiedOlderThan($days . ' days');
        });

        $filter = FilterBuilder::create();
        self::assertSame($filter, $filter->olderThanDays(30));

        self::assertTrue($filter->matches(Items::file('a', lastModified: time() - 40 * 86_400)));
        self::assertFalse($filter->matches(Items::file('a', lastModified: time())));
    }

    #[Test]
    public function unknown_methods_throw(): void
    {
        $this->expectException(BadMethodCallException::class);

        FilterBuilder::create()->doesNotExist();
    }

    #[Test]
    public function macros_cannot_shadow_real_methods(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FilterBuilder::macro('isFile', static fn(): null => null);
    }
}
