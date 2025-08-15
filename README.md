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

$flysystemFilter = new FlysystemFilter();
$filteredResults = $flysystemFilter->filter($flysystemContents, $filter);

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

$flysytemFilter = new FlysystemFilter();
$filteredResults = $flysystemFilter->filter($flysystemContents, $filter);
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

$flysytemFilter = new FlysystemFilter();
$filteredResults = $flysystemFilter->filter($flysystemContents, $filter);
```

## Laravel Integration

From **v3.0.0**, Flysystem Filter ships with native Laravel support — including a Service Provider and a Facade — for seamless integration into your Laravel projects.

### Installation

If installed via Composer in a Laravel application, the package will be automatically discovered thanks to Laravel's package auto-discovery.

**composer.json** (auto-discovery config — already included in this package):

```json
{
  "extra": {
    "laravel": {
      "providers": [
        "Marktaborosi\\FlysystemFilter\\Laravel\\FlysystemFilterServiceProvider"
      ],
      "aliases": {
        "FlysystemFilter": "Marktaborosi\\FlysystemFilter\\Laravel\\FlysystemFilterFacade"
      }
    }
  }
}
```

If you have disabled auto-discovery, register them manually in `config/app.php`:

```php
'providers' => [
    Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterServiceProvider::class,
],
'aliases' => [
    'FlysystemFilter' => Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterFacade::class,
],
```

### Example: Using `FilterBuilder` via the Facade

```php
use Illuminate\Support\Facades\Storage;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use FlysystemFilter; // Facade alias

Route::get('/filtered-files', function () {
    $listing = Storage::disk('local')->listContents('', true);

    $builder = (new FilterBuilder())
        ->isFile()
        ->group_start()
            ->filenameContains('console')
            ->or()
            ->filenameContains('file')
        ->group_end();

    $filtered = FlysystemFilter::filter($listing, $builder);

    return $filtered->map(fn($i) => $i->path())->toArray();
});
```

### Example: Using a Callable Predicate

```php
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;
use FlysystemFilter;

Route::get('/log-files', function () {
    $listing = Storage::disk('local')->listContents('', true);

    // Callable allows custom filter logic without building a `FilterBuilder`
    $callable = static fn(StorageAttributes $item): bool =>
        str_ends_with($item->path(), '.log');

    $filtered = FlysystemFilter::filter($listing, $callable);

    return $filtered->map(fn($i) => $i->path())->toArray();
});
```

### Notes
- `FlysystemFilter::filter()` is **now an instance method** (since v3.0.0) — the Facade makes it look static in Laravel, but under the hood it is resolved from the container.
- All filtering logic and method signatures remain the same as in standalone usage.
- Non-`StorageAttributes` entries in the listing are automatically skipped.

## API Overview

### Filtering Options

#### General Conditions
- `isFile()`: Matches file entries.
- `isDirectory()`: Matches directory entries.

#### Path-Based Conditions
- `pathEquals($paths)`: Matches exact paths. Accepts `string` or `array`.
- `pathContains($substrings)`: Matches paths containing specific substrings.
- `pathNotContains($substrings)`: Excludes paths containing any given substrings.
- `pathMatchesRegex($pattern)`: Matches paths using a regular expression.
- `pathStartsWith($prefixes)`: Matches paths starting with specific prefix(es).
- `pathEndsWith($suffixes)`: Matches paths ending with specific suffix(es).

#### Filename-Based Conditions *(excluding extension)*
- `filenameEquals($filenames)`: Matches exact filenames (excluding extensions). Accepts `string` or `array`.
- `filenameNotEquals($filenames)`: Excludes filenames that match exactly.
- `filenameContains($substrings)`: Matches filenames containing any of the substrings.
- `filenameNotContains($substrings)`: Excludes filenames containing any of the substrings.
- `filenameStartsWith($prefixes)`: Matches filenames starting with any of the specified prefix(es).
- `filenameEndsWith($suffixes)`: Matches filenames ending with any of the specified suffix(es).
- `filenameMatchesRegex($pattern)`: Matches filenames via regular expression.

#### Basename-Based Conditions *(filename including extension)*
- `basenameEquals($basenames)`: Matches the full filename (including extension) exactly.
- `basenameNotEquals($basenames)`: Excludes files with exact matching basenames.
- `basenameContains($substrings)`: Matches basenames containing any of the substrings.
- `basenameNotContains($substrings)`: Excludes basenames containing any of the substrings.
- `basenameStartsWith($prefixes)`: Matches basenames that start with the given prefix(es).
- `basenameEndsWith($suffixes)`: Matches basenames that end with the given suffix(es).

#### Extension-Based Conditions
- `extensionEquals($extensions)`: Matches files with one or more exact extensions.
- `extensionNotEquals($extensions)`: Excludes files with the specified extensions.
- `extensionContains($substrings)`: Matches files whose extension contains any given substring.
- `extensionNotContains($substrings)`: Excludes files whose extension contains any given substring.

#### Size-Based Conditions
- `sizeEquals($size)`: Matches files with an exact size.
- `sizeGt($size)`: Matches files larger than a specific size.
- `sizeGte($size)`: Matches files larger than or equal to a specific size.
- `sizeLt($size)`: Matches files smaller than a specific size.
- `sizeLte($size)`: Matches files smaller than or equal to a specific size.
- `sizeBetween($min, $max)`: Matches files within a size range.

📌 **Note**: Size units can be specified as `B`, `K`, `M`, `G`, `T` (e.g., `'512K'`, `'1G'`).

#### Date-Based Conditions
- `lastModifiedBefore($timestamp)`: Matches files modified before a given timestamp or `Carbon` instance.
- `lastModifiedAfter($timestamp)`: Matches files modified after a given timestamp or `Carbon` instance.
- `lastModifiedBetween($start, $end)`: Matches files modified within a date range.

#### Mime-Type Conditions
- `mimeTypeEquals($mimeTypes)`: Matches files with exact MIME types.
- `mimeTypeNotEquals($mimeTypes)`: Excludes files with specific MIME types.
- `mimeTypeContains($substrings)`: Matches files where the MIME type contains any substring.
- `mimeTypeNotContains($substrings)`: Excludes files whose MIME type contains specific substrings.

📌 **Note**: MIME types are detected from file extensions using `league/mime-type-detection`. No file contents are read.

#### Visibility-Based Conditions
- `isPublic()`: Matches files with `'public'` visibility.
- `isPrivate()`: Matches files with `'private'` visibility.
- `visibilityEquals($visibilities)`: Matches files with exact visibility values.
- `visibilityContains($substrings)`: Matches files whose visibility string contains given substring(s).

#### Depth-Based Conditions
- `depthEquals($depth)`: Matches items with an exact directory depth.
- `depthGt($depth)`: Matches items with a depth greater than the specified value.
- `depthLt($depth)`: Matches items with a depth less than the specified value.

### Logical Operators
- `and()`: Combines conditions with logical AND.
- `or()`: Combines conditions with logical OR.
- `group_start() / group_end()`: Groups conditions for logical precedence (like parentheses).

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
