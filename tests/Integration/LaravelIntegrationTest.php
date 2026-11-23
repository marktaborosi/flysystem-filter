<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Integration;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterFacade;
use Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class LaravelIntegrationTest extends TestCase
{
    /**
     * @param Application $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [FlysystemFilterServiceProvider::class];
    }

    /**
     * @param Application $app
     *
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return ['FlysystemFilter' => FlysystemFilterFacade::class];
    }

    /**
     * @param iterable<StorageAttributes> $listing
     *
     * @return list<string>
     */
    private function paths(iterable $listing): array
    {
        $paths = [];
        foreach ($listing as $item) {
            $paths[] = $item->path();
        }

        return $paths;
    }

    private function fakeDisk(): FilesystemAdapter
    {
        $disk = Storage::fake('local');
        self::assertInstanceOf(FilesystemAdapter::class, $disk);

        return $disk;
    }

    #[Test]
    public function the_facade_alias_is_registered(): void
    {
        self::assertTrue(is_a('FlysystemFilter', FlysystemFilterFacade::class, true));
    }

    #[Test]
    public function the_service_is_a_resolvable_singleton(): void
    {
        $service = $this->app?->make(FlysystemFilter::class);

        self::assertInstanceOf(FlysystemFilter::class, $service);
        self::assertSame($service, $this->app?->make('flysystem.filter'));
        self::assertSame($service, FlysystemFilterFacade::getFacadeRoot());
    }

    #[Test]
    public function facade_filters_with_a_builder(): void
    {
        $disk = $this->fakeDisk();
        $disk->put('keep/file.php', '<?php echo "ok";');
        $disk->put('skip/file.txt', 'not php');

        $listing = $disk->getDriver()->listContents('', true);
        $filtered = FlysystemFilterFacade::filter($listing, FlysystemFilterFacade::builder()->basenameEndsWith('.php'));

        self::assertSame(['keep/file.php'], $this->paths($filtered));
    }

    #[Test]
    public function facade_filters_with_a_callable(): void
    {
        $disk = $this->fakeDisk();
        $disk->put('log/test.log', 'log data');
        $disk->put('src/file.php', '<?php echo "ok";');

        $listing = $disk->getDriver()->listContents('', true);
        $filtered = FlysystemFilterFacade::filter($listing, static fn(StorageAttributes $item): bool => str_ends_with($item->path(), '.log'));

        self::assertSame(['log/test.log'], $this->paths($filtered));
    }

    #[Test]
    public function builder_works_directly_on_laravel_disks(): void
    {
        $disk = $this->fakeDisk();
        $disk->put('a.jpg', 'x');
        $disk->put('b.txt', 'x');

        $filtered = $disk->getDriver()->listContents('', true)->filter(FilterBuilder::create()->isImage());

        self::assertSame(['a.jpg'], $this->paths($filtered));
    }
}
