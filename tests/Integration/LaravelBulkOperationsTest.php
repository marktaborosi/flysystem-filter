<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Integration;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterServiceProvider;
use Marktaborosi\FlysystemFilter\Laravel\StorageFilter;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class LaravelBulkOperationsTest extends TestCase
{
    private FilesystemAdapter $disk;

    private FilesystemAdapter $backup;

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

        $this->disk = $this->fake('local');
        $this->backup = $this->fake('backup');

        $this->disk->put('uploads/cat.jpg', 'cat');
        $this->disk->put('uploads/dog.png', 'dog');
        $this->disk->put('uploads/notes.txt', 'notes');
        $this->disk->put('uploads/2025/big.jpg', 'big');
        $this->disk->put('logs/app.log', 'log');
    }

    private function fake(string $name): FilesystemAdapter
    {
        $disk = Storage::fake($name);
        self::assertInstanceOf(FilesystemAdapter::class, $disk);

        return $disk;
    }

    private function images(string $directory = 'uploads'): StorageFilter
    {
        return StorageFilter::on($this->disk, $directory)->isImage();
    }

    /**
     * @return list<string>
     */
    private function files(FilesystemAdapter $disk): array
    {
        $files = [];
        foreach ($disk->allFiles() as $file) {
            self::assertIsString($file);
            $files[] = $file;
        }
        sort($files);

        return $files;
    }

    #[Test]
    public function delete_removes_only_the_matching_files(): void
    {
        $result = $this->images()->delete();

        self::assertSame('delete', $result->operation);
        self::assertCount(3, $result);
        self::assertTrue($result->successful());
        self::assertSame(['logs/app.log', 'uploads/notes.txt'], $this->files($this->disk));
    }

    #[Test]
    public function delete_ignores_matching_directories(): void
    {
        $result = StorageFilter::on($this->disk, 'uploads')->pathContains('2025')->delete();

        self::assertSame(['uploads/2025/big.jpg'], $result->paths());
        self::assertTrue($this->disk->directoryExists('uploads/2025'));
    }

    #[Test]
    public function move_keeps_the_structure_below_the_query_directory(): void
    {
        $result = $this->images()->move('archive');

        self::assertSame(
            ['uploads/2025/big.jpg' => 'archive/2025/big.jpg', 'uploads/cat.jpg' => 'archive/cat.jpg', 'uploads/dog.png' => 'archive/dog.png'],
            $this->sortedMap($result->map()),
        );
        self::assertSame(
            ['archive/2025/big.jpg', 'archive/cat.jpg', 'archive/dog.png', 'logs/app.log', 'uploads/notes.txt'],
            $this->files($this->disk),
        );
        self::assertSame('cat', $this->disk->get('archive/cat.jpg'));
    }

    #[Test]
    public function move_with_a_path_mapper(): void
    {
        $result = $this->images()->move(static fn(StorageAttributes $file): string => 'flat/' . basename($file->path()));

        self::assertCount(3, $result);
        self::assertTrue($this->disk->exists('flat/big.jpg'));
        self::assertTrue($this->disk->exists('flat/cat.jpg'));
        self::assertFalse($this->disk->exists('uploads/cat.jpg'));
    }

    #[Test]
    public function moving_into_a_listed_directory_processes_each_file_once(): void
    {
        $result = StorageFilter::on($this->disk, 'uploads')->isFile()->move('uploads/archive');

        self::assertCount(4, $result);
        self::assertSame(
            ['logs/app.log', 'uploads/archive/2025/big.jpg', 'uploads/archive/cat.jpg', 'uploads/archive/dog.png', 'uploads/archive/notes.txt'],
            $this->files($this->disk),
        );
    }

    #[Test]
    public function existing_targets_are_skipped_unless_overwrite_is_enabled(): void
    {
        $this->disk->put('archive/cat.jpg', 'old cat');

        $result = $this->images()->copy('archive');

        self::assertCount(2, $result);
        self::assertSame(['uploads/cat.jpg' => 'target exists'], $result->skippedPaths());
        self::assertSame('old cat', $this->disk->get('archive/cat.jpg'));

        $overwritten = $this->images()->copy('archive', overwrite: true);

        self::assertCount(3, $overwritten);
        self::assertSame('cat', $this->disk->get('archive/cat.jpg'));
    }

    #[Test]
    public function same_source_and_target_is_skipped(): void
    {
        $result = StorageFilter::on($this->disk)->extensionEquals('log')->move('', overwrite: true);

        self::assertCount(0, $result);
        self::assertSame(['logs/app.log' => 'source and target are the same'], $result->skippedPaths());
        self::assertTrue($this->disk->exists('logs/app.log'));
    }

    #[Test]
    public function copy_keeps_the_originals(): void
    {
        $result = $this->images()->copy('copies');

        self::assertCount(3, $result);
        self::assertTrue($this->disk->exists('uploads/cat.jpg'));
        self::assertSame('cat', $this->disk->get('copies/cat.jpg'));
    }

    #[Test]
    public function copy_to_another_disk(): void
    {
        $result = $this->images()->copyTo($this->backup, 'images');

        self::assertSame('copyTo', $result->operation);
        self::assertSame(['images/2025/big.jpg', 'images/cat.jpg', 'images/dog.png'], $this->files($this->backup));
        self::assertSame('big', $this->backup->get('images/2025/big.jpg'));
        self::assertTrue($this->disk->exists('uploads/cat.jpg'));
    }

    #[Test]
    public function copy_to_another_disk_keeps_paths_by_default(): void
    {
        StorageFilter::on($this->disk)->extensionEquals('log')->copyTo($this->backup);

        self::assertSame(['logs/app.log'], $this->files($this->backup));
    }

    #[Test]
    public function move_to_another_disk(): void
    {
        $result = $this->images()->moveTo($this->backup, 'images');

        self::assertCount(3, $result);
        self::assertSame(['images/2025/big.jpg', 'images/cat.jpg', 'images/dog.png'], $this->files($this->backup));
        self::assertSame(['logs/app.log', 'uploads/notes.txt'], $this->files($this->disk));
    }

    #[Test]
    public function dry_run_changes_nothing_but_reports_everything(): void
    {
        $this->disk->put('archive/cat.jpg', 'old');
        $query = $this->images();

        $result = $query->dryRun()->move('archive');

        self::assertTrue($result->dryRun);
        self::assertSame('move (dry run): 2 processed, 1 skipped, 0 failed', $result->summary());
        self::assertSame(['uploads/2025/big.jpg' => 'archive/2025/big.jpg', 'uploads/dog.png' => 'archive/dog.png'], $this->sortedMap($result->map()));
        self::assertSame(
            ['archive/cat.jpg', 'logs/app.log', 'uploads/2025/big.jpg', 'uploads/cat.jpg', 'uploads/dog.png', 'uploads/notes.txt'],
            $this->files($this->disk),
        );

        self::assertCount(3, $query->dryRun()->delete());
        self::assertCount(6, $this->disk->allFiles());

        // dryRun() returns a copy: the original query still performs the operation
        self::assertFalse($query->delete()->dryRun);
        self::assertSame(['archive/cat.jpg', 'logs/app.log', 'uploads/notes.txt'], $this->files($this->disk));
    }

    #[Test]
    public function works_through_the_disk_macro(): void
    {
        $query = $this->disk->filter('logs');
        self::assertInstanceOf(StorageFilter::class, $query);

        $result = $query->isFile()->lastModifiedOlderThan('1 hour', now()->addHours(2))->delete();

        self::assertSame(['logs/app.log'], $result->paths());
        self::assertFalse($this->disk->exists('logs/app.log'));
    }

    #[Test]
    public function an_invalid_mapper_result_is_reported_as_a_failure(): void
    {
        $result = $this->images()->move(static fn(): string => '');

        self::assertCount(0, $result);
        self::assertCount(3, $result->failures());
        self::assertInstanceOf(InvalidArgumentException::class, $result->failed[0]['error']);
        self::assertTrue($this->disk->exists('uploads/cat.jpg'));
    }

    #[Test]
    public function a_path_traversal_target_fails_safely(): void
    {
        $result = $this->images()->copy(static fn(): string => '../../escape.jpg');

        self::assertTrue($result->hasFailures());
        self::assertCount(0, $result);
    }

    /**
     * @param array<string, string|null> $map
     *
     * @return array<string, string|null>
     */
    private function sortedMap(array $map): array
    {
        ksort($map);

        return $map;
    }
}
