
# Flysystem Filter

[![Author](https://img.shields.io/badge/author-@marktaborosi-blue.svg)](https://github.com/marktaborosi)
[![Latest Version](https://img.shields.io/github/release/marktaborosi/flysystem-filter.svg?style=flat-square)](https://github.com/marktaborosi/flysystem-filter/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/marktaborosi/flysystem-filter/blob/master/LICENSE)
![php 8.2+](https://img.shields.io/badge/php-min%208.2-red.svg)

Flysystem Filter is a lightweight and intuitive filtering layer for [League/Flysystem](https://flysystem.thephpleague.com/). It provides an easy-to-use `FilterBuilder` for logical and chainable filtering of filesystem contents (`DirectoryListing`).

## Features

- **Simple Filtering:** Filter filesystem contents without writing complex callback functions.
- **Logical Expressions:** Combine conditions using `and()`, `or()`, `group_start()`, and `group_end()`.
- **Chainable API:** Build complex filters with a readable, chainable syntax.
- **Integration with Flysystem:** Works seamlessly with League/Flysystem's `DirectoryListing`.

## Installation

Install via Composer:

```bash
composer require marktaborosi/flysystem-filter
```

## Usage

Here's an example of using Flysystem Filter to filter filesystem contents:

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

require 'vendor/autoload.php';

$adapter = new LocalFilesystemAdapter(__DIR__ . '/tests/Storage/');
$flysystem = new Filesystem($adapter);
$flysystemContents = $flysystem->listContents('', true);

// Create a filter with multiple conditions
$filter = new FilterBuilder();
$filter
    ->group_start()
        ->extensionContains(['log', 'txt'])
        ->and()
        ->extensionNotEquals(['pdf', 'xlsx'])
        ->and()
        ->isFile()
    ->group_end()
    ->or()
    ->filenameMatchesRegex("/console/")
    ->and()
    ->isPublic()
    ->and()
    ->sizeLt("1G");

$filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

foreach ($filteredResults as $result) {
    echo $result->path() . PHP_EOL;
}
```

## API Overview

### FilterBuilder

`FilterBuilder` allows you to create chainable filters for filesystem contents.

#### Conditions

- `isFile()` / `isDirectory()`
- `pathEquals($paths)` / `pathContains($substrings)` / `pathNotEquals($paths)` / `pathNotContains($substrings)`
- `filenameEquals($filenames)` / `filenameNotEquals($filenames)` / `filenameContains($substrings)` / `filenameNotContains($substrings)` / `filenameMatchesRegex($pattern)`
- `basenameEquals($basename)` / `basenameNotEquals($basename)` / `basenameContains($substrings)` / `basenameNotContains($substrings)`
- `extensionEquals($extensions)` / `extensionNotEquals($extensions)` / `extensionContains($substrings)` / `extensionNotContains($substrings)`
- `sizeEquals($size)` / `sizeNotEquals($size)` / `sizeGt($size)` / `sizeGte($size)` / `sizeLt($size)` / `sizeLte($size)` / `sizeBetween($min, $max)`
- `lastModifiedBefore($timestamp)` / `lastModifiedAfter($timestamp)` / `lastModifiedBetween($start, $end)`
- `isPublic()` / `isPrivate()`

#### Logical Operators

- `and()` / `or()`
- `group_start()` / `group_end()`

### FlysystemFilter

The `FlysystemFilter` class applies filters to a `DirectoryListing`:

```php
public static function filter(DirectoryListing $list, FilterBuilder $builder): DirectoryListing;
```

## Requirements

- PHP 8.2 or higher
- League/Flysystem 3.29 or higher

## Development

Run the tests:

```bash
composer test
```

## Contributing

Feel free to contribute to the project by submitting issues or pull requests on [GitHub](https://github.com/marktaborosi/flysystem-filter).

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
