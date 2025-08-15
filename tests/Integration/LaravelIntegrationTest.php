<?php

namespace Marktaborosi\FlysystemFilter\Tests\Integration;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterFacade;
use Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterServiceProvider;
use Orchestra\Testbench\TestCase;

class LaravelIntegrationTest extends TestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            FlysystemFilterServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'FlysystemFilter' => FlysystemFilterFacade::class,
        ];
    }

    /** @test
     * @throws FilesystemException
     */
    public function facade_filters_with_filter_builder(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('keep/file.php', '<?php echo "ok";');
        Storage::disk('local')->put('skip/file.txt', 'not php');

        $listing = Storage::disk('local')->listContents('', true);

        $builder = (new FilterBuilder())->basenameEndsWith('.php');

        $filtered = \FlysystemFilter::filter($listing, $builder);

        $paths = array_map(
            static fn($item): string => $item->path(),
            $filtered->toArray()
        );

        $this->assertContains('keep/file.php', $paths);
        $this->assertNotContains('skip/file.txt', $paths);
    }

    /** @test
     * @throws FilesystemException
     */
    public function facade_filters_with_callable(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('log/test.log', 'log data');
        Storage::disk('local')->put('src/file.php', '<?php echo "ok";');

        $listing = Storage::disk('local')->listContents('', true);

        $callable = static fn($item): bool => str_ends_with($item->path(), '.log');

        $filtered = \FlysystemFilter::filter($listing, $callable);

        $paths = array_map(
            static fn($item): string => $item->path(),
            $filtered->toArray()
        );

        $this->assertContains('log/test.log', $paths);
        $this->assertNotContains('src/file.php', $paths);
    }
}
