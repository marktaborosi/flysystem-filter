<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Integration;

use League\Flysystem\DirectoryListing;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Runs filters against a real local filesystem.
 *
 * tests/Storage contains: another-test-notes.txt, console.log, dummy.jpg, test-notes.txt
 */
final class FlysystemFilterIntegrationTest extends TestCase
{
    private Filesystem $fixtures;

    private string $tempDirectory;

    private Filesystem $temp;

    protected function setUp(): void
    {
        $this->fixtures = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../Storage'));

        $this->tempDirectory = sys_get_temp_dir() . '/flysystem-filter-' . bin2hex(random_bytes(6));
        $this->temp = new Filesystem(new LocalFilesystemAdapter($this->tempDirectory));
        $this->temp->write('logs/2025/app.log', str_repeat('x', 2048));
        $this->temp->write('logs/2025/app.log.gz', 'x');
        $this->temp->write('logs/today.log', 'x');
        $this->temp->write('images/cat.JPG', 'x');
        $this->temp->write('images/raw/dog.png', 'x');
        $this->temp->write('.env', 'SECRET=1');
        $this->temp->write('.git/config', 'x');
        $this->temp->write('empty.txt', '');
    }

    protected function tearDown(): void
    {
        $this->temp->deleteDirectory('');
        @rmdir($this->tempDirectory);
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
        sort($paths);

        return $paths;
    }

    #[Test]
    public function files_only(): void
    {
        $filtered = (new FlysystemFilter())->filter($this->fixtures->listContents('', true), (new FilterBuilder())->isFile());

        self::assertSame(['another-test-notes.txt', 'console.log', 'dummy.jpg', 'test-notes.txt'], $this->paths($filtered));
    }

    #[Test]
    public function grouping_example_from_the_readme(): void
    {
        $filter = (new FilterBuilder())
            ->isFile()
            ->groupStart()->filenameContains('console')->or()->filenameContains('dummy')->groupEnd();

        self::assertSame(['console.log', 'dummy.jpg'], $this->paths($this->fixtures->listContents('', true)->filter($filter)));
    }

    #[Test]
    public function mime_and_size_with_real_metadata(): void
    {
        $listing = fn(): DirectoryListing => $this->fixtures->listContents('', true);

        self::assertSame(['another-test-notes.txt'], $this->paths($listing()->filter(FilterBuilder::create()->isText()->sizeGt(10))));
        self::assertSame(['dummy.jpg'], $this->paths($listing()->filter(FilterBuilder::create()->isImage()->isEmptyFile())));
    }

    #[Test]
    public function realistic_filter_on_a_temporary_tree(): void
    {
        $filter = FilterBuilder::create()
            ->isFile()
            ->not()->isHidden()
            ->where(static fn(FilterBuilder $q): FilterBuilder => $q
                ->pathMatchesGlob('logs/**.{log,gz}')
                ->or()
                ->isImage()->inDirectory('images', recursive: true));

        self::assertSame(['images/cat.JPG', 'images/raw/dog.png', 'logs/2025/app.log', 'logs/2025/app.log.gz', 'logs/today.log'], $this->paths($this->temp->listContents('', true)->filter($filter)));
    }

    #[Test]
    public function hidden_empty_and_recent(): void
    {
        $listing = fn(): DirectoryListing => $this->temp->listContents('', true);

        self::assertSame(['.env', '.git', '.git/config'], $this->paths($listing()->filter(FilterBuilder::create()->isHidden())));
        self::assertSame(['empty.txt'], $this->paths($listing()->filter(FilterBuilder::create()->isEmptyFile())));
        self::assertSame(['logs/2025/app.log'], $this->paths($listing()->filter(FilterBuilder::create()->sizeGte('2K'))));
        self::assertCount(8, $this->paths($listing()->filter(FilterBuilder::create()->isFile()->lastModifiedWithin('1 hour'))));
    }

    #[Test]
    public function serialized_filter_gives_the_same_result(): void
    {
        $filter = FilterBuilder::create()->isFile()->not()->isHidden()->extensionEquals(['log', 'png']);
        $restored = FilterBuilder::fromJson((string) json_encode($filter));

        self::assertSame($this->paths($this->temp->listContents('', true)->filter($filter)), $this->paths($this->temp->listContents('', true)->filter($restored)));
    }
}
