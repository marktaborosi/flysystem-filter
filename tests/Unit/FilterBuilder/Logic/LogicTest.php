<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Logic;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\AnyOf;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogicTest extends TestCase
{
    #[Test]
    public function empty_builder_matches_everything(): void
    {
        $filter = new FilterBuilder();

        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertTrue($filter->matches(Items::dir('docs')));
        self::assertFalse($filter->hasConditions());
        self::assertSame('everything', $filter->describe());
    }

    #[Test]
    public function string_cast_equals_describe(): void
    {
        $filter = (new FilterBuilder())->isFile()->or()->not()->sizeGt('1K');

        self::assertSame('is file OR NOT size > 1 KiB', (string) $filter);
        self::assertSame($filter->describe(), (string) $filter);
    }

    #[Test]
    public function conditions_are_joined_with_and_by_default(): void
    {
        $filter = (new FilterBuilder())->isFile()->extensionEquals('txt');

        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertFalse($filter->matches(Items::file('a.md')));
        self::assertFalse($filter->matches(Items::dir('b.txt')));
    }

    #[Test]
    public function and_binds_tighter_than_or(): void
    {
        // a OR b AND c  ==  a OR (b AND c)
        $filter = (new FilterBuilder())
            ->extensionEquals('md')
            ->or()
            ->isFile()->and()->sizeGt(1000);

        self::assertTrue($filter->matches(Items::file('readme.md', 1)));
        self::assertTrue($filter->matches(Items::file('big.bin', 5000)));
        self::assertFalse($filter->matches(Items::file('small.bin', 10)));
    }

    #[Test]
    public function groups_change_precedence(): void
    {
        $filter = (new FilterBuilder())
            ->pathContains('path')
            ->and()
            ->groupStart()
                ->extensionEquals('txt')
                ->or()
                ->extensionEquals('md')
            ->groupEnd();

        self::assertTrue($filter->matches(Items::file('some/path/file.txt')));
        self::assertTrue($filter->matches(Items::file('some/path/file.md')));
        self::assertFalse($filter->matches(Items::file('some/path/file.csv')));
        self::assertFalse($filter->matches(Items::file('other/file.txt')));
    }

    #[Test]
    public function group_after_condition_is_implicitly_and_ed(): void
    {
        $filter = (new FilterBuilder())
            ->isFile()
            ->groupStart()->filenameContains('console')->or()->filenameContains('dummy')->groupEnd();

        self::assertTrue($filter->matches(Items::file('console.log')));
        self::assertFalse($filter->matches(Items::dir('console')));
        self::assertFalse($filter->matches(Items::file('notes.txt')));
    }

    #[Test]
    public function nested_groups(): void
    {
        // (txt AND (small OR public)) OR dir
        $filter = (new FilterBuilder())
            ->groupStart()
                ->extensionEquals('txt')
                ->groupStart()->sizeLt(10)->or()->isPrivate()->groupEnd()
            ->groupEnd()
            ->or()
            ->isDirectory();

        self::assertTrue($filter->matches(Items::file('a.txt', 5)));
        self::assertTrue($filter->matches(Items::file('a.txt', 500, 'private')));
        self::assertFalse($filter->matches(Items::file('a.txt', 500, 'public')));
        self::assertTrue($filter->matches(Items::dir('docs')));
    }

    #[Test]
    public function not_negates_the_next_condition(): void
    {
        $filter = (new FilterBuilder())->isFile()->not()->isHidden();

        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertFalse($filter->matches(Items::file('.env')));
        self::assertSame('is file AND NOT is hidden', $filter->describe());
    }

    #[Test]
    public function not_negates_a_whole_group(): void
    {
        $filter = (new FilterBuilder())
            ->isFile()
            ->not()->groupStart()->extensionEquals('log')->or()->pathStartsWith('tmp/')->groupEnd();

        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertFalse($filter->matches(Items::file('a.log')));
        self::assertFalse($filter->matches(Items::file('tmp/a.txt')));
        self::assertSame('is file AND NOT (extension equals log OR path starts with tmp/)', $filter->describe());
    }

    #[Test]
    public function or_not_combination(): void
    {
        $filter = (new FilterBuilder())->isDirectory()->or()->not()->extensionEquals('tmp');

        self::assertTrue($filter->matches(Items::dir('docs')));
        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertFalse($filter->matches(Items::file('a.tmp')));
    }

    #[Test]
    public function evaluation_short_circuits_and(): void
    {
        $calls = 0;
        $filter = (new FilterBuilder())
            ->isDirectory()
            ->custom(static function () use (&$calls): bool {
                ++$calls;

                return true;
            });

        self::assertFalse($filter->matches(Items::file('a.txt')));
        self::assertSame(0, $calls);
    }

    #[Test]
    public function evaluation_short_circuits_or(): void
    {
        $calls = 0;
        $filter = (new FilterBuilder())
            ->isFile()
            ->or()
            ->custom(static function () use (&$calls): bool {
                ++$calls;

                return true;
            });

        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertSame(0, $calls);
    }

    #[Test]
    public function compiles_to_a_flat_condition_tree(): void
    {
        $condition = (new FilterBuilder())->isFile()->and()->isPublic()->or()->isDirectory()->toCondition();

        self::assertInstanceOf(AnyOf::class, $condition);
        self::assertCount(2, $condition->conditions);
        self::assertInstanceOf(AllOf::class, $condition->conditions[0]);
    }

    #[Test]
    public function compiled_condition_is_cached_and_invalidated_on_change(): void
    {
        $filter = (new FilterBuilder())->isFile();
        $first = $filter->toCondition();

        self::assertSame($first, $filter->toCondition());

        $filter->extensionEquals('txt');

        self::assertNotSame($first, $filter->toCondition());
        self::assertFalse($filter->matches(Items::file('a.md')));
    }

    #[Test]
    public function builder_is_invokable_for_native_flysystem_filtering(): void
    {
        $filter = (new FilterBuilder())->extensionEquals('txt');

        self::assertTrue($filter(Items::file('a.txt')));
        self::assertFalse($filter(Items::file('a.md')));
    }

    #[Test]
    public function clone_is_independent(): void
    {
        $base = (new FilterBuilder())->isFile()->groupStart()->extensionEquals('txt');
        $copy = clone $base;

        $base->or()->extensionEquals('md')->groupEnd();
        $copy->groupEnd();

        self::assertTrue($base->matches(Items::file('a.md')));
        self::assertFalse($copy->matches(Items::file('a.md')));
        self::assertTrue($copy->matches(Items::file('a.txt')));
    }

    #[Test]
    public function deprecated_snake_case_group_methods_still_work(): void
    {
        $filter = (new FilterBuilder())
            ->isFile()
            ->group_start()->extensionEquals('txt')->or()->extensionEquals('md')->group_end();

        self::assertTrue($filter->matches(Items::file('a.md')));
        self::assertFalse($filter->matches(Items::file('a.csv')));
    }

    #[Test]
    public function builder_is_a_condition_and_can_be_nested(): void
    {
        $images = (new FilterBuilder())->isImage();
        $filter = (new FilterBuilder())->isFile()->where($images)->or()->isDirectory();

        // later changes to the nested builder do not leak into the outer one
        $images->extensionEquals('never');

        self::assertTrue($filter->matches(Items::file('a.png')));
        self::assertTrue($filter->matches(Items::dir('docs')));
        self::assertFalse($filter->matches(Items::file('a.txt')));
    }

    #[Test]
    public function non_matching_custom_predicates_receive_the_item(): void
    {
        $seen = [];
        $filter = (new FilterBuilder())->custom(static function (StorageAttributes $item) use (&$seen): bool {
            $seen[] = $item->path();

            return false;
        });

        $filter->matches(Items::file('x.txt'));

        self::assertSame(['x.txt'], $seen);
    }
}
