<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

include_once __DIR__ . '/../vendor/autoload.php';

/**
 * Example usage of Flysystem with filtering.
 */

// Create a local filesystem adapter for the test storage directory
$adapter = new LocalFilesystemAdapter(__DIR__ . "/../tests/Storage/");

// Flysystem instance configured with the local adapter.
$flysystem = new Filesystem($adapter);

// Retrieve the list of contents from the filesystem recursively.
$flysystemContents = $flysystem->listContents("", true);

// Create a FilterBuilder instance and add conditions
// Filters for: (is File) AND (Filename Contains 'console' OR 'dummy') OR (File size between 1 byte <and> 1 megabyte)
$filter = new FilterBuilder();
$filter
    ->isFile()
    ->and()
    ->group_start()
    ->filenameContains("console")
    ->or()
    ->filenameContains("dummy")
    ->group_end()
    ->or()
    ->sizeBetween("1B","1M");

//Filtered results after applying the filter to the filesystem contents.
$filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

// Output the filtered results for debugging
// Number of items should be 4
foreach ($filteredResults as $result) {
    echo $result->path() . PHP_EOL;
}
