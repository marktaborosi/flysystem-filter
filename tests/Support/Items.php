<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Support;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;

/**
 * Factories for real Flysystem attribute objects used throughout the tests.
 */
final class Items
{
    /**
     * @param array<string, mixed> $extra
     */
    public static function file(
        string $path,
        ?int $size = 100,
        ?string $visibility = 'public',
        ?int $lastModified = null,
        ?string $mimeType = null,
        array $extra = [],
    ): FileAttributes {
        return new FileAttributes($path, $size, $visibility, $lastModified, $mimeType, $extra);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public static function dir(string $path, ?string $visibility = 'public', ?int $lastModified = null, array $extra = []): DirectoryAttributes
    {
        return new DirectoryAttributes($path, $visibility, $lastModified, $extra);
    }
}
