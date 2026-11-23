<?php

declare(strict_types=1);

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

require __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../tests/Storage'));
$listing = $filesystem->listContents('', true);

// Only files.
$filter = FilterBuilder::create()->isFile();

// Since v4 the builder can be passed straight to Flysystem:
foreach ($listing->filter($filter) as $item) {
    echo $item->path() . PHP_EOL; // 4 files
}

// The FlysystemFilter service still works as well:
$again = (new FlysystemFilter())->filter($filesystem->listContents('', true), $filter);
echo count($again->toArray()) . ' files' . PHP_EOL;
