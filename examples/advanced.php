<?php

declare(strict_types=1);

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;

require __DIR__ . '/../vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/../tests/Storage'));
$listing = $filesystem->listContents('', true);

$onlyRecent = true;

// Reusable, named filters.
FilterBuilder::macro('isDocument', static fn(FilterBuilder $q) => $q->extensionEquals(['txt', 'md', 'pdf', 'docx']));

$filter = FilterBuilder::create()
    ->isFile()
    ->not()->isHidden()
    ->whereAny([
        static fn(FilterBuilder $q) => $q->isDocument(),
        static fn(FilterBuilder $q) => $q->pathMatchesGlob('**.{log,gz}'),
    ])
    ->sizeBetween('1B', '10M')
    ->when($onlyRecent, static fn(FilterBuilder $q) => $q->lastModifiedWithin('10 years'));

echo 'Filter: ' . $filter . PHP_EOL;

foreach ($listing->filter($filter) as $item) {
    echo ' - ' . $item->path() . PHP_EOL;
}

// Filters (without closures) can be stored or sent over the wire as JSON …
$json = json_encode(FilterBuilder::create()->isFile()->extensionEquals('log')->sizeGt('1K'), JSON_PRETTY_PRINT);
echo $json . PHP_EOL;

// … and restored later. Input is validated, so it may come from a request.
$restored = FilterBuilder::fromJson((string) $json);
echo 'Restored: ' . $restored . PHP_EOL;
