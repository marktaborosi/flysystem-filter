<?php

declare(strict_types=1);

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;

require __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../tests/Storage'));
$listing = $filesystem->listContents('', true);

// is file AND (filename contains "console" OR filename contains "dummy")
$filter = FilterBuilder::create()
    ->isFile()
    ->groupStart()
        ->filenameContains('console')
        ->or()
        ->filenameContains('dummy')
    ->groupEnd();

foreach ($listing->filter($filter) as $item) {
    echo $item->path() . PHP_EOL; // console.log, dummy.jpg
}
