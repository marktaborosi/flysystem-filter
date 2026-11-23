<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Logic;

use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\IsFile;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WhereTest extends TestCase
{
    #[Test]
    public function where_closure_creates_a_group(): void
    {
        $filter = (new FilterBuilder())
            ->isFile()
            ->where(static fn(FilterBuilder $q) => $q->extensionEquals('log')->or()->pathStartsWith('debug/'));

        self::assertTrue($filter->matches(Items::file('a.log')));
        self::assertTrue($filter->matches(Items::file('debug/a.txt')));
        self::assertFalse($filter->matches(Items::file('a.txt')));
        self::assertSame('is file AND (extension equals log OR path starts with debug/)', $filter->describe());
    }

    #[Test]
    public function where_accepts_condition_objects(): void
    {
        $longPath = new class implements Condition {
            public function matches(StorageAttributes $item): bool
            {
                return \strlen($item->path()) > 10;
            }

            public function describe(): string
            {
                return 'long path';
            }
        };

        $filter = (new FilterBuilder())->where(new IsFile())->where($longPath);

        self::assertTrue($filter->matches(Items::file('a/very/long/path.txt')));
        self::assertFalse($filter->matches(Items::file('short.txt')));
        self::assertSame('is file AND long path', $filter->describe());
    }

    #[Test]
    public function where_closure_with_typed_item_parameter_is_a_predicate(): void
    {
        $filter = (new FilterBuilder())->where(static fn(FileAttributes $item): bool => $item->fileSize() === 42);

        self::assertTrue($filter->matches(Items::file('a', 42)));
        self::assertFalse($filter->matches(Items::file('a', 41)));
    }

    #[Test]
    public function or_where_and_where_not(): void
    {
        $filter = (new FilterBuilder())
            ->isDirectory()
            ->orWhere(static fn(FilterBuilder $q) => $q->extensionEquals('txt')->whereNot(static fn(FilterBuilder $n) => $n->pathStartsWith('tmp/')));

        self::assertTrue($filter->matches(Items::dir('docs')));
        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertFalse($filter->matches(Items::file('tmp/a.txt')));
        self::assertFalse($filter->matches(Items::file('a.md')));
    }

    #[Test]
    public function or_where_on_empty_builder_behaves_like_where_for_loops(): void
    {
        $filter = new FilterBuilder();

        foreach (['txt', 'md'] as $extension) {
            $filter->orWhere(static fn(FilterBuilder $q) => $q->extensionEquals($extension));
        }

        self::assertTrue($filter->matches(Items::file('a.md')));
        self::assertFalse($filter->matches(Items::file('a.csv')));
    }

    #[Test]
    public function or_where_not(): void
    {
        $filter = (new FilterBuilder())->isDirectory()->orWhereNot(static fn(FilterBuilder $q) => $q->sizeGt('1K'));

        self::assertTrue($filter->matches(Items::dir('docs')));
        self::assertTrue($filter->matches(Items::file('small', 10)));
        self::assertFalse($filter->matches(Items::file('big', 5000)));
    }

    #[Test]
    public function empty_where_group_is_ignored_together_with_its_operator(): void
    {
        $filter = (new FilterBuilder())->isFile()->or()->where(static fn(FilterBuilder $q) => $q)->not()->where(static fn(FilterBuilder $q) => null);

        self::assertTrue($filter->matches(Items::file('a.txt')));
        self::assertFalse($filter->matches(Items::dir('docs')));
    }

    #[Test]
    public function where_any_and_where_all(): void
    {
        $filter = (new FilterBuilder())
            ->whereAny([
                static fn(FilterBuilder $q) => $q->extensionEquals('jpg'),
                static fn(FilterBuilder $q) => $q->extensionEquals('png'),
            ])
            ->whereAll([new IsFile(), static fn(FilterBuilder $q) => $q->sizeLt('1M')]);

        self::assertTrue($filter->matches(Items::file('a.png', 10)));
        self::assertFalse($filter->matches(Items::file('a.png', 5_000_000)));
        self::assertFalse($filter->matches(Items::file('a.gif', 10)));
        self::assertSame('(extension equals jpg OR extension equals png) AND is file AND size < 1 MiB', $filter->describe());
    }

    #[Test]
    public function where_any_with_empty_list_is_ignored(): void
    {
        $filter = (new FilterBuilder())->isFile()->whereAny([]);

        self::assertTrue($filter->matches(Items::file('a')));
        self::assertSame('is file', $filter->describe());
    }

    #[Test]
    public function when_and_unless(): void
    {
        $build = static fn(bool $onlyLogs, bool $includeHidden): FilterBuilder => (new FilterBuilder())
            ->isFile()
            ->when($onlyLogs, static fn(FilterBuilder $q) => $q->extensionEquals('log'))
            ->unless($includeHidden, static fn(FilterBuilder $q) => $q->not()->isHidden());

        self::assertSame('is file AND extension equals log AND NOT is hidden', $build(true, false)->describe());
        self::assertSame('is file', $build(false, true)->describe());
    }

    #[Test]
    public function when_passes_the_value_and_supports_a_default(): void
    {
        $filter = (new FilterBuilder())
            ->when('logs', static fn(FilterBuilder $q, mixed $dir) => $q->inDirectory(\is_string($dir) ? $dir : ''))
            ->when(0, static fn(FilterBuilder $q) => $q->isDirectory(), static fn(FilterBuilder $q) => $q->isFile())
            ->when(static fn(): bool => true, static fn(FilterBuilder $q) => $q->not()->isHidden());

        self::assertSame('dirname equals logs AND is file AND NOT is hidden', $filter->describe());
    }
}
