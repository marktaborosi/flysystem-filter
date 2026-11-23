<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\Support;

use Marktaborosi\FlysystemFilter\Support\Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SizeTest extends TestCase
{
    /**
     * @return iterable<string, array{int|string, int}>
     */
    public static function sizes(): iterable
    {
        yield 'int' => [1234, 1234];
        yield 'digits' => ['1234', 1234];
        yield 'bytes' => ['512B', 512];
        yield 'K' => ['10K', 10_240];
        yield 'lower k' => ['10k', 10_240];
        yield 'KB' => ['10KB', 10_240];
        yield 'KiB' => ['10KiB', 10_240];
        yield 'M' => ['5M', 5 * 1024 ** 2];
        yield 'decimal' => ['1.5M', 1_572_864];
        yield 'space' => ['2 GB', 2 * 1024 ** 3];
        yield 'T' => ['1T', 1024 ** 4];
        yield 'P' => ['1PiB', 1024 ** 5];
    }

    #[Test]
    #[DataProvider('sizes')]
    public function parses_sizes(int|string $input, int $bytes): void
    {
        self::assertSame($bytes, Size::toBytes($input));
    }

    #[Test]
    public function formats_sizes(): void
    {
        self::assertSame('0 B', Size::format(0));
        self::assertSame('1023 B', Size::format(1023));
        self::assertSame('1 KiB', Size::format(1024));
        self::assertSame('1.5 MiB', Size::format(1_572_864));
        self::assertSame('2 GiB', Size::format(2 * 1024 ** 3));
    }
}
