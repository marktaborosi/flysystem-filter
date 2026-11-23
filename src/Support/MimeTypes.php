<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Support;

use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

/**
 * @internal
 */
final class MimeTypes
{
    private static ?MimeTypeDetector $default = null;

    /**
     * Extension based detector: never reads file contents and does not need ext-fileinfo.
     */
    public static function defaultDetector(): MimeTypeDetector
    {
        return self::$default ??= new ExtensionMimeTypeDetector();
    }
}
