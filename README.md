
# Flysystem Filter

[![Author](https://img.shields.io/badge/author-@marktaborosi-blue.svg)](https://github.com/marktaborosi)
[![Latest Version](https://img.shields.io/github/release/marktaborosi/flysystem-filter.svg?style=flat-square)](https://github.com/marktaborosi/flysystem-filter/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/marktaborosi/flysystem-filter/blob/master/LICENSE)
![Downloads](https://img.shields.io/packagist/dt/marktaborosi/flysystem-filter.svg)
![php 8.2+](https://img.shields.io/badge/php-min%208.2-red.svg)
[![CI](https://github.com/marktaborosi/flysystem-filter/actions/workflows/test.yml/badge.svg)](https://github.com/marktaborosi/flysystem-filter/actions)

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

### Simple Example

Here's a basic example that filters only files:

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

require 'vendor/autoload.php';

$adapter = new LocalFilesystemAdapter(__DIR__ . '/tests/Storage/');
$flysystem = new Filesystem($adapter);
$flysystemContents = $flysystem->listContents('', true);

$filter = new FilterBuilder();
$filter->isFile();

$filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

foreach ($filteredResults as $result) {
    echo $result->path() . PHP_EOL;
}
```

### Advanced Example

Filter files with advanced conditions:

```php
$filter = new FilterBuilder();
$filter
    ->extensionEquals(['txt', 'log'])
    ->and()
    ->isPublic()
    ->and()
    ->sizeLt('1G');

$filteredResults = FlysystemFilter::filter($flysystemContents, $filter);
```

### Using Grouping and Logical Operators

You can group conditions to create complex expressions:

```php
$filter = new FilterBuilder();
$filter
    ->group_start()
        ->extensionContains(['log', 'txt'])
        ->and()
        ->isFile()
    ->group_end()
    ->or()
    ->pathMatchesRegex('/debug/');

$filteredResults = FlysystemFilter::filter($flysystemContents, $filter);
```

## API Overview

### Filtering Options

#### General Conditions
- `isFile()`: Matches file entries.
- `isDirectory()`: Matches directory entries.

#### Path-Based Conditions
- `pathEquals($paths)`: Matches exact paths. Accepts `string` or `array`.
- `pathContains($substrings)`: Matches paths containing specific substrings. Accepts `string` or `array`.
- `pathMatchesRegex($pattern)`: Matches paths using a regex pattern.

#### Filename-Based Conditions
- `filenameEquals($filenames)`: Matches filenames without extensions. Accepts `string` or `array`.
- `filenameContains($substrings)`: Matches filenames containing specific substrings. Accepts `string` or `array`.
- `filenameMatchesRegex($pattern)`: Matches filenames using a regex pattern.

#### Size-Based Conditions
- `sizeEquals($size)`: Matches files of a specific size.
- `sizeGt($size)`: Matches files larger than a specific size.
- `sizeLt($size)`: Matches files smaller than a specific size.

📌 **Note**: Sizes can be specified in units like `B`, `K`, `M`, `G`, `T`.

#### Date-Based Conditions
- `lastModifiedBefore($timestamp)`: Matches files modified before a specific timestamp.
- `lastModifiedAfter($timestamp)`: Matches files modified after a specific timestamp.

#### Mime-type Conditions
- `mimeTypeEquals($mimeTypes)`: Matches files with a specific MIME type. Accepts string or array (e.g. 'image/png').
- `mimeTypeNotEquals($mimeTypes)`: Matches files that do not have the specified MIME type(s).
- `mimeTypeContains($substrings)`: Matches files whose MIME type contains any given substring (e.g. 'image', 'text').
- `mimeTypeNotContains($substrings)`: Matches files whose MIME type does not contain any of the given substrings.

📌 **Note**: MIME type detection is performed using the file path via league/mime-type-detection. No file contents are read.

### Logical Operators
- `and()`: Combines conditions with logical AND.
- `or()`: Combines conditions with logical OR.
- `group_start() / group_end()`: Groups conditions to control logical precedence.

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

---

> Made with ❤️ by [Mark Taborosi](https://github.com/marktaborosi)