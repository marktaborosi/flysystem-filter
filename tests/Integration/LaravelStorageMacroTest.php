<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Integration;

use BadMethodCallException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Exception\InvalidFilterException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterServiceProvider;
use Marktaborosi\FlysystemFilter\Laravel\StorageFilter;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class LaravelStorageMacroTest extends TestCase
{
    private FilesystemAdapter $disk;

    /**
     * @param Application $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [FlysystemFilterServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $disk = Storage::fake('local');
        self::assertInstanceOf(FilesystemAdapter::class, $disk);
        $this->disk = $disk;

        $this->disk->put('uploads/cat.jpg', str_repeat('x', 6000));
        $this->disk->put('uploads/dog.png', 'x');
        $this->disk->put('uploads/notes.txt', 'x');
        $this->disk->put('uploads/2025/big.jpg', str_repeat('x', 9000));
        $this->disk->put('uploads/.hidden.jpg', str_repeat('x', 9000));
        $this->disk->put('logs/app.log', 'x');
    }

    protected function tearDown(): void
    {
        FilterBuilder::flushMacros();
        parent::tearDown();
    }

    /**
     * Calls the disk macro. PHPStan cannot see Laravel macros without Larastan.
     */
    private function query(string $directory = '', bool $recursive = true, ?FilterBuilder $builder = null): StorageFilter
    {
        $query = $this->disk->filter($directory, $recursive, $builder);
        self::assertInstanceOf(StorageFilter::class, $query);

        return $query;
    }

    /**
     * @param iterable<string> $paths
     *
     * @return list<string>
     */
    private static function sorted(iterable $paths): array
    {
        $list = [...$paths];
        sort($list);

        return $list;
    }

    #[Test]
    public function the_macro_is_registered(): void
    {
        self::assertTrue(FilesystemAdapter::hasMacro('filter'));
        self::assertInstanceOf(StorageFilter::class, $this->disk->filter());
    }

    #[Test]
    public function filters_a_disk_with_builder_methods(): void
    {
        $paths = $this->query('uploads')
            ->isImage()
            ->sizeGt('5K')
            ->not()->isHidden()
            ->paths();

        self::assertInstanceOf(LazyCollection::class, $paths);
        self::assertSame(['uploads/2025/big.jpg', 'uploads/cat.jpg'], self::sorted($paths));
    }

    #[Test]
    public function works_on_the_default_disk_through_the_facade(): void
    {
        $filter = Storage::filter('logs');

        self::assertInstanceOf(StorageFilter::class, $filter);
        self::assertSame(['logs/app.log'], self::sorted($filter->isFile()->paths()));
    }

    #[Test]
    public function lists_the_whole_disk_by_default(): void
    {
        self::assertSame(
            ['logs/app.log', 'uploads/.hidden.jpg', 'uploads/2025/big.jpg', 'uploads/cat.jpg', 'uploads/dog.png', 'uploads/notes.txt'],
            self::sorted($this->query()->isFile()->paths()),
        );
    }

    #[Test]
    public function can_be_non_recursive(): void
    {
        self::assertSame(
            ['uploads/.hidden.jpg', 'uploads/cat.jpg', 'uploads/dog.png', 'uploads/notes.txt'],
            self::sorted($this->query('uploads', recursive: false)->isFile()->paths()),
        );
        self::assertSame(['uploads/2025'], self::sorted($this->query('uploads', false)->isDirectory()->paths()));
    }

    #[Test]
    public function an_existing_builder_can_be_passed_and_is_not_modified(): void
    {
        $images = FilterBuilder::create()->isImage();

        $paths = $this->query('uploads', true, $images)->not()->isHidden()->sizeLt('1K')->paths();

        self::assertSame(['uploads/dog.png'], self::sorted($paths));
        self::assertSame('MIME type starts with image/', $images->describe());
    }

    #[Test]
    public function terminal_methods(): void
    {
        $txt = fn(): StorageFilter => $this->query('uploads')->extensionEquals('txt');
        $none = fn(): StorageFilter => $this->query('uploads')->extensionEquals('exe');

        $first = $txt()->first();
        self::assertInstanceOf(StorageAttributes::class, $first);
        self::assertSame('uploads/notes.txt', $first->path());
        self::assertNull($none()->first());

        self::assertTrue($txt()->exists());
        self::assertFalse($txt()->doesntExist());
        self::assertFalse($none()->exists());
        self::assertTrue($none()->doesntExist());

        self::assertCount(1, $txt());
        self::assertCount(0, $none());

        $items = $txt()->get()->all();
        self::assertCount(1, $items);
        self::assertContainsOnlyInstancesOf(StorageAttributes::class, $items);

        $iterated = [];
        foreach ($txt() as $item) {
            $iterated[] = $item->path();
        }
        self::assertSame(['uploads/notes.txt'], $iterated);
    }

    #[Test]
    public function a_query_can_be_reused(): void
    {
        $query = $this->query('uploads')->extensionEquals('png');

        self::assertSame(['uploads/dog.png'], self::sorted($query->paths()));
        self::assertSame(['uploads/dog.png'], self::sorted($query->paths()));

        $this->disk->put('uploads/new.png', 'x');

        self::assertSame(['uploads/dog.png', 'uploads/new.png'], self::sorted($query->paths()));
    }

    #[Test]
    public function non_chainable_builder_methods_return_their_own_result(): void
    {
        $query = $this->query()->isFile()->extensionEquals('log');

        self::assertTrue($query->hasConditions());
        self::assertSame('is file AND extension equals log', $query->describe());
        self::assertSame('is file AND extension equals log', (string) $query);
        self::assertSame($query->builder()->toArray(), FilterBuilder::create()->isFile()->extensionEquals('log')->toArray());
    }

    #[Test]
    public function filter_builder_macros_are_available(): void
    {
        FilterBuilder::macro('isPhoto', static fn(FilterBuilder $q) => $q->extensionEquals(['jpg', 'jpeg']));

        $query = $this->query('uploads');
        $query->isPhoto();

        self::assertSame(['uploads/.hidden.jpg', 'uploads/2025/big.jpg', 'uploads/cat.jpg'], self::sorted($query->paths()));
    }

    #[Test]
    public function an_incomplete_filter_fails_when_the_query_runs(): void
    {
        $query = $this->query()->isFile()->or();

        $this->expectException(InvalidFilterException::class);

        $query->paths();
    }

    #[Test]
    public function unknown_methods_throw(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('StorageFilter::nope() does not exist');

        $this->query()->nope();
    }

    #[Test]
    public function magic_builder_methods_are_not_forwarded(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->query()->__clone();
    }

    #[Test]
    public function an_existing_filter_macro_is_not_overwritten(): void
    {
        $provider = new FlysystemFilterServiceProvider($this->app ?? self::fail('No application.'));
        FilesystemAdapter::flushMacros();

        try {
            // Macroable binds the closure to the disk, so it must not be static.
            FilesystemAdapter::macro('filter', fn(): string => 'custom');
            $provider->boot();

            self::assertSame('custom', $this->disk->filter());
        } finally {
            FilesystemAdapter::flushMacros();
            $provider->boot();
        }

        self::assertInstanceOf(StorageFilter::class, $this->disk->filter());
    }
}
