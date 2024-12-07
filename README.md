
# Flysystem Filter

[![Author](https://img.shields.io/badge/author-@marktaborosi-blue.svg)](https://github.com/marktaborosi)
[![Latest Version](https://img.shields.io/github/release/marktaborosi/flysystem-filter.svg?style=flat-square)](https://github.com/marktaborosi/flysystem-filter/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/marktaborosi/flysystem-filter/blob/master/LICENSE)
![php 8.2+](https://img.shields.io/badge/php-min%208.2-red.svg)

Flysystem Filter is a lightweight and intuitive filtering layer for [League/Flysystem](https://flysystem.thephpleague.com/). It provides an easy-to-use `FilterBuilder` for logical and chainable filtering of filesystem contents (`DirectoryListing`).

---

## Features

- **Simple Filtering:** Filter filesystem contents without writing complex callback functions.
- **Logical Expressions:** Combine conditions using `and()`, `or()`, `group_start()`, and `group_end()`.
- **Chainable API:** Build complex filters with a readable, chainable syntax.
- **Integration with Flysystem:** Works seamlessly with League/Flysystem's `DirectoryListing`.

---

## Installation

Install via Composer:

```bash
composer require marktaborosi/flysystem-filter
```

---

## Basic Usage

Here's a basic example of filtering files only:

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;

require 'vendor/autoload.php';

// Setup Flysystem
$adapter = new LocalFilesystemAdapter(__DIR__ . '/tests/Storage/');
$flysystem = new Filesystem($adapter);
$flysystemContents = $flysystem->listContents('', true);

// Filter to include only files
$filter = new FilterBuilder();
$filter->isFile();

$filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

foreach ($filteredResults as $result) {
    echo $result->path() . PHP_EOL;
}
```

---

## Advanced Usage

### Example 1: Filtering by Extension

```php
$filter = new FilterBuilder();
$filter->extensionEquals(['txt', 'log']);
```

Filters for items with `.txt` or `.log` extensions.

---

### Example 2: Filtering by File Size Range

```php
$filter = new FilterBuilder();
$filter->sizeBetween("1K", "1M");
```

Filters for items with file sizes between 1 kilobyte and 1 megabyte.

---

### Example 3: Combining Conditions with Groups

```php
$filter = new FilterBuilder();
$filter
    ->group_start()
        ->extensionEquals(['txt', 'log'])
        ->and()
        ->isFile()
    ->group_end()
    ->or()
    ->group_start()
        ->sizeLt("1M")
        ->and()
        ->isPublic()
    ->group_end();
```

This example demonstrates the use of logical grouping to combine conditions:
- Items with `.txt` or `.log` extensions that are files.
- OR
- Items that are less than 1 megabyte in size and publicly accessible.

---

## Filtering Options

### General Conditions

| Method                | Description                                                                                 | Example Usage                                |
|-----------------------|---------------------------------------------------------------------------------------------|---------------------------------------------|
| `isFile()`            | Filters for files only.                                                                    | `$filter->isFile();`                        |
| `isDirectory()`       | Filters for directories only.                                                              | `$filter->isDirectory();`                   |

---

### Path-Based Conditions

| Method                | Description                                                                                 | Example Usage                                |
|-----------------------|---------------------------------------------------------------------------------------------|---------------------------------------------|
| `pathEquals($paths)`  | Matches exact path(s).                                                                      | `$filter->pathEquals(['/path/to/file']);`    |
| `pathContains($substrings)` | Matches paths containing specific substrings.                                         | `$filter->pathContains(['sub']);`           |
| `pathMatchesRegex($pattern)` | Matches paths using a regex pattern.                                                 | `$filter->pathMatchesRegex('/file/');`      |

---

### Filename-Based Conditions

| Method                | Description                                                                                 | Example Usage                                |
|-----------------------|---------------------------------------------------------------------------------------------|---------------------------------------------|
| `filenameEquals($filenames)` | Matches filenames without extensions.                                                | `$filter->filenameEquals(['file']);`        |
| `filenameContains($substrings)` | Matches filenames containing substrings.                                          | `$filter->filenameContains(['name']);`      |

---

### Size-Based Conditions

| Method                | Description                                                                                 | Example Usage                                |
|-----------------------|---------------------------------------------------------------------------------------------|---------------------------------------------|
| `sizeEquals($size)`   | Matches files of a specific size.                                                           | `$filter->sizeEquals("1M");`                |
| `sizeBetween($min, $max)` | Matches files within a size range.                                                      | `$filter->sizeBetween("1K", "1M");`         |

**Note:** Size strings can use units like `B`, `K`, `M`, `G`, and `T`.

---

## Logical Operators

Use these methods to build complex conditions:
- `and()`
- `or()`
- `group_start()`
- `group_end()`

For example:

```php
$filter->group_start()->isFile()->and()->sizeLt("1M")->group_end();
```

---

## Development

Run the tests:

```bash
composer test
```

---

## Contributing

Feel free to contribute to the project by submitting issues or pull requests on [GitHub](https://github.com/marktaborosi/flysystem-filter).

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
