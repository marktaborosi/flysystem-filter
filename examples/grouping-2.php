<?php

declare(strict_types=1);

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;

require __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../tests/Storage'));
$listing = $filesystem->listContents('', true);

// The same expression with closures and negation:
// is file AND (filename contains "notes" OR extension equals log) AND NOT is hidden
$filter = FilterBuilder::create()
    ->isFile()
    ->where(fn(FilterBuilder $q) => $q->filenameContains('notes')->or()->extensionEquals('log'))
    ->whereNot(fn(FilterBuilder $q) => $q->isHidden());

echo $filter . PHP_EOL;

foreach ($listing->filter($filter) as $item) {
    echo ' - ' . $item->path() . PHP_EOL;
}
