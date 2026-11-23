<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FlysystemFilter;

use ArrayObject;
use Generator;
use League\Flysystem\DirectoryListing;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\IsDirectory;
use Marktaborosi\FlysystemFilter\Exception\InvalidFilterException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

final class FlysystemFilterTest extends TestCase
{
    /**
     * @return list<StorageAttributes>
     */
    private static function items(): array
    {
        return [
            Items::file('test/file1.txt'),
            Items::file('test/file2.log'),
            Items::dir('test/subdir'),
        ];
    }

    /**
     * @param iterable<StorageAttributes> $listing
     *
     * @return list<string>
     */
    private static function paths(iterable $listing): array
    {
        $paths = [];
        foreach ($listing as $item) {
            $paths[] = $item->path();
        }

        return $paths;
    }

    #[Test]
    public function filters_with_a_builder(): void
    {
        $filtered = (new FlysystemFilter())->filter(
            new DirectoryListing(self::items()),
            (new FilterBuilder())->isFile()->and()->extensionEquals('txt'),
        );

        self::assertSame(['test/file1.txt'], self::paths($filtered));
    }

    #[Test]
    public function empty_builder_keeps_everything(): void
    {
        $items = self::items();
        $filtered = (new FlysystemFilter())->filter(new DirectoryListing($items), new FilterBuilder());

        self::assertSame($items, $filtered->toArray());
    }

    #[Test]
    public function filters_with_a_callable(): void
    {
        $filtered = (new FlysystemFilter())->filter(
            new DirectoryListing(self::items()),
            static fn(StorageAttributes $item): bool => str_ends_with($item->path(), '.log'),
        );

        self::assertSame(['test/file2.log'], self::paths($filtered));
    }

    #[Test]
    public function filters_with_a_condition_object(): void
    {
        $filtered = (new FlysystemFilter())->filter(self::items(), new IsDirectory());

        self::assertSame(['test/subdir'], self::paths($filtered));
    }

    #[Test]
    public function accepts_any_iterable_and_skips_foreign_entries(): void
    {
        $filtered = (new FlysystemFilter())->filter(
            ['not an item', new stdClass(), Items::file('a.txt'), null],
            new FilterBuilder(),
        );

        self::assertSame(['a.txt'], self::paths($filtered));
    }

    #[Test]
    public function result_is_lazy(): void
    {
        $counter = new ArrayObject();
        $source = (static function () use ($counter): Generator {
            foreach (['a.txt', 'b.txt', 'c.txt'] as $path) {
                $counter->append($path);
                yield Items::file($path);
            }
        })();

        $filtered = (new FlysystemFilter())->filter($source, new FilterBuilder());
        self::assertCount(0, $counter);

        foreach ($filtered as $item) {
            self::assertSame('a.txt', $item->path());
            break;
        }
        self::assertCount(1, $counter);
    }

    #[Test]
    public function incomplete_builder_fails_fast_before_iteration(): void
    {
        $this->expectException(InvalidFilterException::class);

        (new FlysystemFilter())->filter([], (new FilterBuilder())->isFile()->or());
    }

    #[Test]
    public function builder_works_with_native_directory_listing_filter(): void
    {
        $filtered = (new DirectoryListing(self::items()))->filter((new FilterBuilder())->isDirectory());

        self::assertSame(['test/subdir'], self::paths($filtered));
    }

    #[Test]
    public function creates_builders(): void
    {
        self::assertFalse((new FlysystemFilter())->builder()->hasConditions());
        self::assertNotSame((new FlysystemFilter())->builder(), (new FlysystemFilter())->builder());
    }
}
