<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\Bulk;

use ArrayObject;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use Marktaborosi\FlysystemFilter\Bulk\BulkResult;
use Marktaborosi\FlysystemFilter\Exception\BulkOperationException;
use Marktaborosi\FlysystemFilter\Exception\FilterException;
use Marktaborosi\FlysystemFilter\Laravel\StorageFilter;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BulkResultTest extends TestCase
{
    #[Test]
    public function accessors(): void
    {
        $error = new RuntimeException('disk full');
        $result = new BulkResult(
            'copy',
            false,
            [['from' => 'a.txt', 'to' => 'b/a.txt']],
            [['from' => 'c.txt', 'to' => 'b/c.txt', 'reason' => 'target exists']],
            [['from' => 'd.txt', 'to' => 'b/d.txt', 'error' => $error]],
        );

        self::assertCount(1, $result);
        self::assertSame(['a.txt'], $result->paths());
        self::assertSame(['a.txt' => 'b/a.txt'], $result->map());
        self::assertSame(['c.txt' => 'target exists'], $result->skippedPaths());
        self::assertSame(['d.txt' => $error], $result->failures());
        self::assertFalse($result->successful());
        self::assertTrue($result->hasFailures());
        self::assertSame('copy: 1 processed, 1 skipped, 1 failed', $result->summary());
    }

    #[Test]
    public function throw_returns_the_result_when_everything_succeeded(): void
    {
        $result = new BulkResult('delete', false, [['from' => 'a.txt', 'to' => null]]);

        self::assertSame($result, $result->throw());
        self::assertTrue($result->successful());
    }

    #[Test]
    public function the_exception_is_a_package_exception(): void
    {
        self::assertContains(FilterException::class, class_implements(BulkOperationException::class));
    }

    #[Test]
    public function throw_raises_an_exception_with_the_result(): void
    {
        $error = new RuntimeException('permission denied');
        $result = new BulkResult('delete', false, [], [], [['from' => 'a.txt', 'to' => null, 'error' => $error]]);

        try {
            $result->throw();
            self::fail('Expected an exception.');
        } catch (BulkOperationException $exception) {
            self::assertSame($result, $exception->result);
            self::assertSame($error, $exception->getPrevious());
            self::assertSame('Bulk delete failed for 1 file(s), first: "a.txt": permission denied.', $exception->getMessage());
        }
    }

    #[Test]
    public function failing_files_do_not_stop_the_operation(): void
    {
        $deleted = new ArrayObject();
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('listContents')->willReturn(new DirectoryListing([
            Items::file('a.txt'),
            Items::file('locked.txt'),
            Items::file('c.txt'),
        ]));
        $filesystem->method('delete')->willReturnCallback(static function (string $path) use ($deleted): void {
            if ($path === 'locked.txt') {
                throw UnableToDeleteFile::atLocation($path, 'locked');
            }
            $deleted->append($path);
        });

        $result = (new StorageFilter($filesystem))->delete();

        self::assertSame(['a.txt', 'c.txt'], $deleted->getArrayCopy());
        self::assertSame(['a.txt', 'c.txt'], $result->paths());
        self::assertSame(['locked.txt'], array_keys($result->failures()));

        $this->expectException(BulkOperationException::class);
        $result->throw();
    }

    #[Test]
    public function a_failing_existence_check_is_reported(): void
    {
        $filesystem = $this->createMock(FilesystemOperator::class);
        $filesystem->method('listContents')->willReturn(new DirectoryListing([Items::file('a.txt')]));
        $filesystem->method('fileExists')->willThrowException(new RuntimeException('network down'));
        $filesystem->expects(self::never())->method('copy');

        $result = (new StorageFilter($filesystem))->copy('backup');

        self::assertSame(['a.txt'], array_keys($result->failures()));
    }
}
