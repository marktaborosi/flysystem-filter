<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\Laravel;

use ArrayObject;
use Generator;
use League\Flysystem\DirectoryListing;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Laravel\StorageFilter;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class StorageFilterTest extends TestCase
{
    #[Test]
    public function works_with_a_plain_flysystem_filesystem(): void
    {
        $filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../../Storage'));

        $paths = StorageFilter::on($filesystem)->isFile()->extensionEquals('txt')->paths()->all();
        sort($paths);

        self::assertSame(['another-test-notes.txt', 'test-notes.txt'], $paths);
    }

    #[Test]
    public function first_and_exists_stop_listing_early(): void
    {
        $consumed = new ArrayObject();
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('listContents')->willReturnCallback(
            static fn(): DirectoryListing => new DirectoryListing((static function () use ($consumed): Generator {
                foreach (['a.txt', 'b.jpg', 'c.jpg', 'd.jpg'] as $path) {
                    $consumed->append($path);
                    yield Items::file($path);
                }
            })()),
        );

        $query = new StorageFilter($filesystem);
        $query->extensionEquals('jpg');

        self::assertSame('b.jpg', $query->first()?->path());
        self::assertSame(['a.txt', 'b.jpg'], $consumed->getArrayCopy());

        $consumed->exchangeArray([]);
        self::assertTrue($query->exists());
        self::assertCount(2, $consumed);
    }

    #[Test]
    public function the_directory_and_recursion_are_passed_to_flysystem(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->expects(self::once())
            ->method('listContents')
            ->with('uploads', false)
            ->willReturn(new DirectoryListing([]));

        self::assertCount(0, new StorageFilter($filesystem, 'uploads', false));
    }

    #[Test]
    public function every_chainable_builder_method_is_annotated(): void
    {
        $docComment = (string) (new ReflectionClass(StorageFilter::class))->getDocComment();
        $notForwardedOnPurpose = ['matches', 'toArray', 'jsonSerialize'];
        $missing = [];

        foreach ((new ReflectionClass(FilterBuilder::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($method->isStatic() || str_starts_with($name, '__') || \in_array($name, $notForwardedOnPurpose, true)) {
                continue;
            }

            if (preg_match('/@method \S+ ' . preg_quote($name, '/') . '\(/', $docComment) !== 1) {
                $missing[] = $name;
            }
        }

        self::assertSame([], $missing, 'Add @method annotations to StorageFilter for: ' . implode(', ', $missing));
    }
}
