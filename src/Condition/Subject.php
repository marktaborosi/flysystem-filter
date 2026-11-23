<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\MimeTypeDetector;

/**
 * The string property of a Flysystem item that a {@see StringCondition} inspects.
 *
 * For a path like `docs/report.final.pdf`:
 *
 * | Subject    | Value               |
 * |------------|---------------------|
 * | Path       | `docs/report.final.pdf` |
 * | Dirname    | `docs`              |
 * | Basename   | `report.final.pdf`  |
 * | Filename   | `report.final`      |
 * | Extension  | `pdf`               |
 * | MimeType   | `application/pdf`   |
 * | Visibility | `public` / `private` / adapter specific |
 */
enum Subject: string
{
    case Path = 'path';
    case Dirname = 'dirname';
    case Basename = 'basename';
    case Filename = 'filename';
    case Extension = 'extension';
    case MimeType = 'mimeType';
    case Visibility = 'visibility';

    /**
     * Extracts the subject value from the item. Returns null when the value is unknown
     * (no extension, undetectable MIME type, missing visibility, or a directory for
     * file-only subjects).
     */
    public function resolve(StorageAttributes $item, MimeTypeDetector $mimeTypeDetector): ?string
    {
        $path = $item->path();

        return match ($this) {
            self::Path => $path,
            self::Dirname => self::dirname($path),
            self::Basename => basename($path),
            self::Filename => pathinfo($path, PATHINFO_FILENAME),
            self::Extension => $item instanceof DirectoryAttributes ? null : self::extension($path),
            self::MimeType => self::mimeType($item, $mimeTypeDetector),
            self::Visibility => $item->visibility(),
        };
    }

    /**
     * Whether values of this subject are conventionally compared case-insensitively
     * (extensions and MIME types).
     */
    public function isCaseInsensitiveByDefault(): bool
    {
        return $this === self::Extension || $this === self::MimeType;
    }

    public function label(): string
    {
        return match ($this) {
            self::MimeType => 'MIME type',
            default => $this->value,
        };
    }

    private static function dirname(string $path): string
    {
        $dirname = \dirname(trim($path, '/'));

        return $dirname === '.' ? '' : $dirname;
    }

    private static function extension(string $path): ?string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension === '' ? null : $extension;
    }

    private static function mimeType(StorageAttributes $item, MimeTypeDetector $mimeTypeDetector): ?string
    {
        if (!$item instanceof FileAttributes) {
            return null;
        }

        return $item->mimeType() ?? $mimeTypeDetector->detectMimeTypeFromPath($item->path());
    }
}
