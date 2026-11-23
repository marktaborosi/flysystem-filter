<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileTypeFiltersTraitTest extends TestCase
{
    #[Test]
    public function is_file_and_is_directory(): void
    {
        self::assertTrue((new FilterBuilder())->isFile()->matches(Items::file('a.txt')));
        self::assertFalse((new FilterBuilder())->isFile()->matches(Items::dir('a')));
        self::assertTrue((new FilterBuilder())->isDirectory()->matches(Items::dir('a')));
        self::assertFalse((new FilterBuilder())->isDirectory()->matches(Items::file('a.txt')));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hiddenPaths(): iterable
    {
        yield 'dot file' => ['.env', true];
        yield 'nested dot file' => ['config/.env.local', true];
        yield 'inside dot directory' => ['.git/config', true];
        yield 'regular file' => ['src/app.php', false];
        yield 'dot in the middle' => ['archive.tar.gz', false];
        yield 'relative segments are not hidden' => ['./a/../b.txt', false];
    }

    #[Test]
    #[DataProvider('hiddenPaths')]
    public function is_hidden(string $path, bool $expected): void
    {
        self::assertSame($expected, (new FilterBuilder())->isHidden()->matches(Items::file($path)));
        self::assertSame(!$expected, (new FilterBuilder())->not()->isHidden()->matches(Items::file($path)));
    }
}
