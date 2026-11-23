<?php

declare(strict_types=1);

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;

require __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../tests/Storage'));
$listing = $filesystem->listContents('', true);

// Text or log files, public and smaller than 1 GiB (conditions are AND-ed by default).
$filter = FilterBuilder::create()
    ->extensionEquals(['txt', 'log'])
    ->isPublic()
    ->sizeLt('1G');

echo $filter . PHP_EOL; // extension equals [txt, log] AND visibility equals public AND size < 1 GiB

foreach ($listing->filter($filter) as $item) {
    echo ' - ' . $item->path() . PHP_EOL;
}
