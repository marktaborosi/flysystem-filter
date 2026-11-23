<?php

declare(strict_types=1);

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;

require __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../tests/Storage'));
$listing = $filesystem->listContents('', true);

// Images (MIME type detected from the extension, or taken from the adapter when available).
foreach ($listing->filter(FilterBuilder::create()->isImage()) as $item) {
    echo $item->path() . PHP_EOL; // dummy.jpg
}

// Anything textual except logs:
$filter = FilterBuilder::create()->mimeTypeMatches('text/*')->not()->extensionEquals('log');

foreach ($filesystem->listContents('', true)->filter($filter) as $item) {
    echo $item->path() . PHP_EOL;
}
