# Flysystem Filter

[![Author](https://img.shields.io/badge/author-@marktaborosi-blue.svg)](https://github.com/marktaborosi)
[![Latest Version](https://img.shields.io/github/release/marktaborosi/flysystem-filter.svg?style=flat-square)](https://github.com/marktaborosi/flysystem-filter/releases)
[![Packagist Version](https://img.shields.io/packagist/v/marktaborosi/flysystem-filter.svg)](https://packagist.org/packages/marktaborosi/flysystem-filter)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/marktaborosi/flysystem-filter/blob/main/LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/marktaborosi/flysystem-filter.svg)](https://packagist.org/packages/marktaborosi/flysystem-filter/stats)
![PHP 8.2 – 8.5](https://img.shields.io/badge/php-8.2%20%E2%80%93%208.5-777bb4.svg)
![Laravel 11 | 12 | 13](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)
![PHPStan level max](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)
[![CI](https://github.com/marktaborosi/flysystem-filter/actions/workflows/test.yml/badge.svg)](https://github.com/marktaborosi/flysystem-filter/actions/workflows/test.yml)

Flysystem Filter is a lightweight and intuitive filtering layer for [League/Flysystem](https://flysystem.thephpleague.com/). It provides an easy-to-use, chainable `FilterBuilder` for filtering filesystem contents (`DirectoryListing`) with real boolean logic. You don't need to write complex callback functions.

```php
$filter = FilterBuilder::create()
    ->isFile()
    ->not()->isHidden()
    ->extensionEquals(['jpg', 'png'])
    ->lastModifiedWithin('7 days');

$images = $filesystem->listContents('uploads', true)->filter($filter);
```

## Table of contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Guide](#guide)
  - [Combining conditions: `and()`, `or()`](#combining-conditions-and-or)
  - [Grouping: `groupStart()`, `groupEnd()`, `where()`](#grouping-groupstart-groupend-where)
  - [Negation: `not()`](#negation-not)
  - [Dynamic filters: `when()`, `whereAny()`, loops](#dynamic-filters-when-whereany-loops)
  - [Error handling](#error-handling)
  - [Debugging: `describe()`](#debugging-describe)
- [API reference](#api-reference)
- [Extending](#extending)
- [Saving and loading filters (JSON)](#saving-and-loading-filters-json)
- [Laravel integration](#laravel-integration)
  - [Filtering a disk: `Storage::disk()->filter()`](#filtering-a-disk-storagedisk-filter)
  - [Bulk operations: delete, move, copy](#bulk-operations-delete-move-copy)
- [Upgrading from 3.x](#upgrading-from-3x)
- [Development](#development)

## Features

- **Simple filtering:** filter filesystem contents with readable, chainable methods instead of callbacks.
- **70+ filters:** path, filename, basename, extension, glob, regex, size, date, MIME type, visibility, depth, hidden files and adapter metadata.
- **Logical expressions:** `and()`, `or()`, `not()` and grouping. AND binds tighter than OR, exactly like in PHP.
- **Safe and fast:** the builder compiles into a tree of condition objects that is evaluated with short-circuiting. No `eval()`.
- **Fails early:** mistakes such as `->or()->or()`, an empty group or an invalid regex throw a clear exception right where they happen.
- **Native Flysystem integration:** pass the builder straight to `$listing->filter($builder)`.
- **Serializable:** save filters as JSON and load them back, safely.
- **Extensible:** your own condition classes, custom callbacks and macros.
- **Laravel 11, 12 and 13:** filter any disk with `Storage::disk('s3')->filter('uploads')->isImage()->paths()`, then delete, move or copy the matches in bulk (with dry runs). An auto-discovered service provider and facade are included. Projects without Laravel don't pull in any Laravel dependency.

## Requirements

- PHP 8.2 – 8.5
- League/Flysystem 3.29 or higher
- *(optional)* Laravel 11, 12 or 13

## Installation

Install via Composer:

```bash
composer require marktaborosi/flysystem-filter
```

## Quick start

### Simple example

Here's a basic example that keeps only files:

```php
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;

require 'vendor/autoload.php';

$filesystem = new Filesystem(new LocalFilesystemAdapter(__DIR__ . '/storage'));

$filter = FilterBuilder::create()->isFile();

foreach ($filesystem->listContents('', true)->filter($filter) as $item) {
    echo $item->path() . PHP_EOL;
}
```

### Advanced example

Text or log files that are public and smaller than 1 GB:

```php
$filter = FilterBuilder::create()
    ->extensionEquals(['txt', 'log'])
    ->and()
    ->isPublic()
    ->and()
    ->sizeLt('1G');

$results = $filesystem->listContents('', true)->filter($filter);
```

> `and()` is optional: conditions written one after another are joined with AND. `->extensionEquals(['txt', 'log'])->isPublic()->sizeLt('1G')` means the same.

### Using `FlysystemFilter`

The `FlysystemFilter` class still works as before. It also accepts any iterable, a callable or a condition object, and it validates the filter before iterating:

```php
use Marktaborosi\FlysystemFilter\FlysystemFilter;

$flysystemFilter = new FlysystemFilter();
$results = $flysystemFilter->filter($filesystem->listContents('', true), $filter);
```

> The result is a lazy, generator-backed `DirectoryListing`, so you can iterate it once. Call `->toArray()` if you need it more than once.

## Guide

### Combining conditions: `and()`, `or()`

| You write | It means |
|---|---|
| `->a()->b()` | `a AND b` |
| `->a()->and()->b()` | `a AND b` |
| `->a()->or()->b()` | `a OR b` |
| `->a()->or()->b()->and()->c()` | `a OR (b AND c)`: AND binds tighter, like in PHP |

```php
// Markdown files, or any file larger than 1 MB
$filter = FilterBuilder::create()
    ->extensionEquals('md')
    ->or()
    ->isFile()->and()->sizeGt('1M');
```

### Grouping: `groupStart()`, `groupEnd()`, `where()`

Groups work like parentheses. You can write them in two equivalent ways:

```php
// is file AND (filename contains "console" OR filename contains "dummy")

// 1) with groupStart() / groupEnd()
$filter = FilterBuilder::create()
    ->isFile()
    ->groupStart()
        ->filenameContains('console')
        ->or()
        ->filenameContains('dummy')
    ->groupEnd();

// 2) with a closure
$filter = FilterBuilder::create()
    ->isFile()
    ->where(fn (FilterBuilder $q) => $q->filenameContains('console')->or()->filenameContains('dummy'));
```

Groups can be nested to any depth. `orWhere()` is a shortcut for `->or()->where(...)`.

> `group_start()` / `group_end()` from earlier versions still work, but they are deprecated.

### Negation: `not()`

`not()` negates the **next** condition or group:

```php
$filter = FilterBuilder::create()
    ->isFile()
    ->not()->isHidden()                       // skip .env, .git/…
    ->not()->groupStart()                     // and skip temporary files
        ->extensionEquals('tmp')
        ->or()
        ->pathStartsWith('cache/')
    ->groupEnd();

// The same with closures:
$filter = FilterBuilder::create()
    ->isFile()
    ->whereNot(fn (FilterBuilder $q) => $q->isHidden())
    ->whereNot(fn (FilterBuilder $q) => $q->extensionEquals('tmp')->or()->pathStartsWith('cache/'));
```

### Dynamic filters: `when()`, `whereAny()`, loops

```php
$filter = FilterBuilder::create()
    ->isFile()
    // only applied when the condition is true
    ->when($onlyRecent, fn (FilterBuilder $q) => $q->lastModifiedWithin('30 days'))
    // only applied when the condition is false
    ->unless($includeHidden, fn (FilterBuilder $q) => $q->not()->isHidden())
    // at least one of them must match
    ->whereAny([
        fn (FilterBuilder $q) => $q->isImage(),
        fn (FilterBuilder $q) => $q->isVideo(),
    ]);

// In a loop: orWhere() on an empty builder acts like where()
$filter = FilterBuilder::create();
foreach ($extensions as $extension) {
    $filter->orWhere(fn (FilterBuilder $q) => $q->extensionEquals($extension));
}
```

### Error handling

Mistakes are reported where they happen, with a clear message:

```php
FilterBuilder::create()->isFile()->or()->or();
// InvalidFilterException: Cannot call or() directly after or(); a condition is required between two logical operators.

FilterBuilder::create()->groupStart()->groupEnd();
// InvalidFilterException: Empty group: add at least one condition between groupStart() and groupEnd().

FilterBuilder::create()->sizeLt('10X');
// InvalidArgumentException: Invalid size value "10X". Use bytes (1024) or a number with a unit: …

FilterBuilder::create()->pathMatchesRegex('/[unclosed/');
// InvalidArgumentException: Invalid regular expression "/[unclosed/": Compilation failed: …
```

An unfinished filter (an open group, or a trailing `or()` or `not()`) throws when you use it.

All exceptions extend `LogicException` and implement `Marktaborosi\FlysystemFilter\Exception\FilterException`.

### Debugging: `describe()`

Print any filter to see how it is interpreted:

```php
echo FilterBuilder::create()
    ->isFile()
    ->where(fn (FilterBuilder $q) => $q->extensionEquals('log')->or()->sizeGt('1M'));

// is file AND (extension equals log OR size > 1 MiB)
```

## API reference

Every method returns the builder, so calls can be chained.

**General rules**

- Methods that take a value also accept a **list of values**. The condition matches when **any** of them matches: `extensionEquals(['jpg', 'png'])`.
- `…Not…` methods match when **none** of the values match.
- String filters take an optional `$ignoreCase` argument (default `false`). Extensions and MIME types are case-insensitive by default.
- Any filter can be negated with `not()`.

### General conditions

- `isFile()`: Matches file entries.
- `isDirectory()`: Matches directory entries.
- `isHidden()`: Matches dot entries (`.env`, `.git`) and everything inside dot directories (`.git/config`). Use `->not()->isHidden()` to skip them.

### Path-based conditions

The full path, e.g. `logs/2025/app.log`.

- `pathEquals($paths)`: Matches exact paths.
- `pathNotEquals($paths)`: Excludes exact paths.
- `pathContains($substrings)`: Matches paths containing any of the substrings.
- `pathNotContains($substrings)`: Excludes paths containing any of the substrings.
- `pathStartsWith($prefixes)`: Matches paths starting with any of the prefixes, e.g. `'uploads/'`.
- `pathEndsWith($suffixes)`: Matches paths ending with any of the suffixes.
- `pathMatchesRegex($patterns)`: Matches paths against a regular expression (with delimiters), e.g. `'#^logs/\d{4}/#'`.
- `pathMatchesGlob($patterns)`: Matches paths against a glob pattern, e.g. `'logs/**.{log,gz}'` (see [Glob patterns](#glob-patterns)).
- `inDirectory($directories, $recursive = false)`: Matches items directly inside a directory (`''` is the root). With `recursive: true`, items in sub-directories match too.

```php
$filter->inDirectory('logs');                   // logs/app.log
$filter->inDirectory('logs', recursive: true);  // logs/app.log, logs/2025/01/app.log
```

### Filename-based conditions *(without extension)*

For `docs/report-2025.pdf` the filename is `report-2025`.

- `filenameEquals($filenames)`: Matches exact filenames.
- `filenameNotEquals($filenames)`: Excludes exact filenames.
- `filenameContains($substrings)`: Matches filenames containing any of the substrings.
- `filenameNotContains($substrings)`: Excludes filenames containing any of the substrings.
- `filenameStartsWith($prefixes)`: Matches filenames starting with any of the prefixes.
- `filenameEndsWith($suffixes)`: Matches filenames ending with any of the suffixes, e.g. `'_final'`.
- `filenameMatchesRegex($patterns)`: Matches filenames against a regular expression.
- `filenameMatchesGlob($patterns)`: Matches filenames against a glob pattern, e.g. `'report-20??'`.

### Basename-based conditions *(filename with extension)*

For `docs/report-2025.pdf` the basename is `report-2025.pdf`.

- `basenameEquals($basenames)`: Matches exact basenames.
- `basenameNotEquals($basenames)`: Excludes exact basenames.
- `basenameContains($substrings)`: Matches basenames containing any of the substrings.
- `basenameNotContains($substrings)`: Excludes basenames containing any of the substrings.
- `basenameStartsWith($prefixes)`: Matches basenames starting with any of the prefixes.
- `basenameEndsWith($suffixes)`: Matches basenames ending with any of the suffixes, e.g. `'.tar.gz'`.
- `basenameMatchesRegex($patterns)`: Matches basenames against a regular expression.
- `basenameMatchesGlob($patterns)`: Matches basenames against a glob pattern, e.g. `'*.{jpg,png}'`.

### Extension-based conditions

Case-insensitive by default. A leading dot is optional (`'txt'` and `'.txt'` mean the same). Directories have no extension.

- `extensionEquals($extensions)`: Matches files with any of the extensions.
- `extensionNotEquals($extensions)`: Excludes files with any of the extensions. Files without an extension match.
- `extensionContains($substrings)`: Matches extensions containing any of the substrings, e.g. `'ml'` matches `html` and `xml`.
- `extensionNotContains($substrings)`: Excludes extensions containing any of the substrings.
- `hasExtension()`: Matches files that have an extension.

### MIME type conditions

The MIME type reported by the adapter is used when available. Otherwise it is detected from the file extension; no file contents are read. Directories have no MIME type. Comparisons are case-insensitive.

- `mimeTypeEquals($mimeTypes)`: Matches exact MIME types, e.g. `'image/png'`.
- `mimeTypeNotEquals($mimeTypes)`: Excludes exact MIME types.
- `mimeTypeContains($substrings)`: Matches MIME types containing any of the substrings, e.g. `'zip'`.
- `mimeTypeNotContains($substrings)`: Excludes MIME types containing any of the substrings.
- `mimeTypeMatches($patterns)`: Matches MIME types against a glob pattern, e.g. `'image/*'` or `'application/{zip,gzip}'`.
- `isImage()`, `isVideo()`, `isAudio()`, `isText()`: Shortcuts for `image/*`, `video/*`, `audio/*` and `text/*`.

### Size-based conditions

Sizes are bytes (`1024`) or a number with a unit: `'512B'`, `'10K'`, `'10KB'`, `'10KiB'`, `'1.5M'`, `'2 GB'`, `'1T'`. Units are binary (1K = 1024 bytes).

- `sizeEquals($size)`: Matches files of exactly this size.
- `sizeNotEquals($size)`: Matches files of any other size.
- `sizeGt($size)` / `sizeGte($size)`: Matches files larger than (or equal to) the size.
- `sizeLt($size)` / `sizeLte($size)`: Matches files smaller than (or equal to) the size.
- `sizeBetween($min, $max)`: Matches files within the range (inclusive).
- `isEmptyFile()`: Matches empty (0 byte) files.

📌 **Note:** directories, and files whose size the adapter did not report, never match a size condition.

### Date-based conditions

Dates can be a UNIX timestamp, any `DateTimeInterface` (Carbon, CarbonImmutable, DateTimeImmutable, …) or a date string (`'2025-01-31'`, `'yesterday'`).

- `lastModifiedBefore($date)`: Matches items modified before the date.
- `lastModifiedAfter($date)`: Matches items modified after the date.
- `lastModifiedBetween($start, $end)`: Matches items modified within the range (inclusive).
- `lastModifiedWithin($interval)`: Matches items modified within the last interval, e.g. `'7 days'`, `'12 hours'`, `'1 week 2 days'`, `'P1M'`.
- `lastModifiedOlderThan($interval)`: Matches items not modified for longer than the interval, e.g. `'30 days'`.

📌 **Note:** interval units are `y`/`year`, `mo`/`month`, `w`/`week`, `d`/`day`, `h`/`hour`, `m`/`min`/`minute` and `s`/`sec`/`second` (singular or plural). Items without a known modification time never match.

### Visibility-based conditions

- `isPublic()`: Matches items with `public` visibility.
- `isPrivate()`: Matches items with `private` visibility.
- `visibilityEquals($visibilities)`: Matches exact visibility values (useful for custom adapters).
- `visibilityNotEquals($visibilities)`: Excludes exact visibility values.
- `visibilityContains($substrings)`: Matches visibility values containing any of the substrings.

### Depth-based conditions

Depth is the number of directories above the item: `file.txt` → 0, `docs/file.txt` → 1, `a/b/c/file.txt` → 3.

- `depthEquals($depth)`: Matches items at exactly this depth.
- `depthGt($depth)` / `depthGte($depth)`: Matches items deeper than (or at) the depth.
- `depthLt($depth)` / `depthLte($depth)`: Matches items shallower than (or at) the depth.
- `depthBetween($min, $max)`: Matches items within the range (inclusive).

### Metadata conditions

Adapter specific extra metadata, e.g. `ETag` or `StorageClass` on AWS S3.

- `hasMetadata($key)`: Matches items that have the metadata key.
- `metadataEquals($key, $value)`: Matches items where the metadata value is exactly `$value`.
- `metadataIn($key, $values)`: Matches items where the metadata value is one of `$values`.

### Custom conditions

- `custom($callback, $description = null)`: Matches items for which your callback returns `true`.

```php
$filter->custom(fn (StorageAttributes $item): bool => strlen($item->path()) > 100, 'long path');
```

### Logical operators

- `and()`: Combines conditions with logical AND (optional, this is the default).
- `or()`: Combines conditions with logical OR.
- `not()`: Negates the next condition or group.
- `groupStart()` / `groupEnd()`: Groups conditions like parentheses.
- `where($closureOrCondition)`: Adds a group (closure) or a condition object.
- `orWhere(…)`, `whereNot(…)`, `orWhereNot(…)`: Shortcuts for `or()->where()`, `not()->where()` and `or()->not()->where()`.
- `whereAny([...])` / `whereAll([...])`: At least one / all of the given groups or conditions must match.
- `when($value, $callback, $default = null)` / `unless(…)`: Applies the callback only when `$value` is true / false.

### Builder utilities

- `FilterBuilder::create()`: Creates a new builder (same as `new FilterBuilder()`).
- `matches($item)`: Checks a single item. The builder is also callable: `$filter($item)`.
- `describe()` / `(string) $filter`: Human-readable form of the filter.
- `hasConditions()`: Whether any condition has been added.
- `toCondition()`: Returns the compiled condition tree.
- `toArray()`, `json_encode($filter)`, `FilterBuilder::fromArray()`, `FilterBuilder::fromJson()`: See [Saving and loading filters](#saving-and-loading-filters-json).

### Glob patterns

| Pattern | Matches |
|---|---|
| `*` | any characters except `/` |
| `**` | any characters including `/` (`**/` also matches zero directories) |
| `?` | exactly one character except `/` |
| `[abc]`, `[a-z]` | one character from the set or range |
| `[!abc]`, `[^abc]` | one character **not** in the set |
| `{jpg,png}` | any of the alternatives (can be nested) |
| `\*` | a literal `*` |

## Extending

### Your own condition classes

Implement the `Condition` interface and add the class with `where()`:

```php
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\Condition;

final class OwnedBy implements Condition
{
    public function __construct(private string $owner) {}

    public function matches(StorageAttributes $item): bool
    {
        return ($item->extraMetadata()['owner'] ?? null) === $this->owner;
    }

    public function describe(): string
    {
        return "owned by {$this->owner}";
    }
}

$filter = FilterBuilder::create()->isFile()->where(new OwnedBy('mark'));
```

Implement `SerializableCondition` instead if you also want to save the condition as JSON.

### Macros

Register reusable, named filters once, then use them like built-in methods:

```php
FilterBuilder::macro('isLog', fn (FilterBuilder $q) => $q->where(
    fn (FilterBuilder $g) => $g->extensionEquals(['log', 'gz'])->and()->inDirectory('logs', recursive: true),
));

FilterBuilder::macro('olderThanDays', fn (FilterBuilder $q, int $days) => $q->lastModifiedOlderThan("$days days"));

$filter = FilterBuilder::create()->isLog()->olderThanDays(30);
```

### Custom MIME type detection

```php
$filter = new FilterBuilder(new \League\MimeTypeDetection\FinfoMimeTypeDetector());
```

## Saving and loading filters (JSON)

Filters without closures can be saved, for example as a stored search or a filter sent by an API client, and loaded back later:

```php
$json = json_encode($filter);              // or $filter->toArray()

$filter = FilterBuilder::fromJson($json);   // or FilterBuilder::fromArray($array)
```

The input is strictly validated and never executes code. Regular expressions from users can still be slow, so you can disable them for untrusted input:

```php
use Marktaborosi\FlysystemFilter\Serialization\ConditionSerializer;

$filter = FilterBuilder::fromArray($request->input('filter'), new ConditionSerializer(allowRegex: false));
```

Your own `SerializableCondition` classes can be registered with `(new ConditionSerializer())->register('ownedBy', fn (array $data) => new OwnedBy($data['owner']))`.

## Laravel integration

Laravel 11, 12 and 13 are supported.

### Installation

The service provider and the `FlysystemFilter` facade alias are registered automatically by Laravel's package auto-discovery:

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

If you have disabled auto-discovery, register them manually. On Laravel 11+, add the provider to `bootstrap/providers.php` and the alias to `config/app.php`:

```php
// bootstrap/providers.php
return [
    // ...
    Marktaborosi\FlysystemFilter\Laravel\FlysystemFilterServiceProvider::class,
];
```

### Filtering a disk: `Storage::disk()->filter()`

*Since 4.1.* Every Laravel disk gets a `filter()` method. Chain any filter from the [API reference](#api-reference), then call a method that returns the results:

```php
use Illuminate\Support\Facades\Storage;

// Paths of large images in uploads/ (and its sub-directories) on S3
$paths = Storage::disk('s3')
    ->filter('uploads')
    ->isImage()
    ->sizeGt('5M')
    ->not()->isHidden()
    ->paths();

// On the default disk
$oldLogs = Storage::filter('logs')->isFile()->lastModifiedOlderThan('30 days')->count();
```

`filter($directory = '', $recursive = true, $builder = null)` takes:

- `$directory`: the directory to list, relative to the disk root (`''` is the whole disk)
- `$recursive`: whether sub-directories are included (default `true`)
- `$builder`: an existing `FilterBuilder` to start from (it is copied, not modified)

Methods that return the results:

- `paths()`: the matching paths as a `LazyCollection` of strings
- `get()`: the matching items (`StorageAttributes`) as a `LazyCollection`
- `first()`: the first matching item, or `null`. Listing stops as soon as it is found.
- `exists()` / `doesntExist()`: whether anything matches. Listing stops at the first match.
- `count()`: the number of matching items
- `foreach ($query as $item)`: iterate the matching items

Results are lazy, so the disk is only listed while you consume them. Every call lists the disk again, so a query can be reused:

```php
$pdfs = Storage::disk('s3')->filter('invoices')->isFile()->extensionEquals('pdf');

$pdfs->count();                                                       // 42
$pdfs->paths()->each(fn (string $path) => dispatch(new ArchiveInvoice($path)));
```

> Directories are included in the results too. Add `->isFile()` when you only want files.

**Reusing filters.** Pass a `FilterBuilder`, or build one from a query with `builder()`:

```php
$images = FilterBuilder::create()->isImage()->not()->isHidden();

Storage::disk('public')->filter('avatars', true, $images)->paths();
Storage::disk('s3')->filter('uploads', true, $images)->sizeGt('10M')->paths();
```

**Static analysis.** IDEs and PHPStan only see Laravel macros through [Larastan](https://github.com/larastan/larastan) or [laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper). Without them, use the fully typed equivalent:

```php
use Marktaborosi\FlysystemFilter\Laravel\StorageFilter;

StorageFilter::on(Storage::disk('s3'), 'uploads')->isImage()->paths();
```

If another package already registered a `filter` macro on disks, this package keeps it and does not register its own. Use `StorageFilter::on()` in that case.

### Bulk operations: delete, move, copy

*Since 4.1.* The matching files of a disk query can be deleted, moved or copied in one call. The query can also copy or move them to another disk:

```php
// Delete temporary files older than a week
Storage::disk('local')->filter('tmp')->isFile()->lastModifiedOlderThan('7 days')->delete();

// Archive last year's invoices: uploads/invoices/2025/a.pdf → archive/2025/a.pdf
Storage::disk('s3')->filter('uploads/invoices')->extensionEquals('pdf')->move('archive');

// Back up exports to another disk
Storage::disk('local')->filter('exports')->isFile()->copyTo(Storage::disk('s3'), 'backups/exports');
```

| Method | What it does |
|---|---|
| `delete()` | Deletes the matching files |
| `move($to, $overwrite = false)` | Moves the matching files on the same disk |
| `copy($to, $overwrite = false)` | Copies the matching files on the same disk |
| `copyTo($disk, $to = '', $overwrite = false)` | Copies the matching files to another disk (streamed) |
| `moveTo($disk, $to = '', $overwrite = false)` | Copies the files to another disk, then deletes each original once its copy succeeded |
| `dryRun()` | Returns a copy of the query whose operations only report what they would do |

**Targets.** `$to` can be a directory or a closure:

- **A directory:** the structure below the query's directory is kept, so `filter('uploads')->move('archive')` moves `uploads/2025/a.jpg` to `archive/2025/a.jpg`. With `copyTo()` / `moveTo()` the default `''` keeps the paths as they are.
- **A closure:** it receives each file and returns its full target path, e.g. `->move(fn (StorageAttributes $file) => 'flat/' . basename($file->path()))`.

**Safe by default.**

- Only **files** are touched. Matching directories are ignored.
- Existing target files are **skipped**. Pass `overwrite: true` to replace them.
- All matches are collected before anything changes. A file moved into a directory that is being listed is never processed twice.
- One failing file does not stop the operation: every file is attempted, and failures are reported.

**Preview with a dry run:**

```php
$preview = Storage::disk('s3')->filter('tmp')->lastModifiedOlderThan('30 days')->dryRun()->delete();

$preview->summary();   // "delete (dry run): 128 processed, 0 skipped, 0 failed"
$preview->paths();     // the files that would be deleted
```

**The result.** Every operation returns a `BulkResult`:

```php
$result = Storage::disk('s3')->filter('uploads')->isImage()->move('archive');

count($result);           // number of processed files
$result->paths();         // processed source paths
$result->map();           // ['uploads/a.jpg' => 'archive/a.jpg', ...]
$result->skippedPaths();  // ['uploads/b.jpg' => 'target exists', ...]
$result->failures();      // ['uploads/c.jpg' => Throwable, ...]
$result->successful();    // true when nothing failed
$result->summary();       // "move: 12 processed, 1 skipped, 0 failed"

$result->throw();         // throws BulkOperationException if anything failed, otherwise returns the result
```

### Example: using `listContents()`

Laravel disks return a Flysystem `DirectoryListing`, so the builder also works without the macro:

```php
use Illuminate\Support\Facades\Storage;
use Marktaborosi\FlysystemFilter\FilterBuilder;

$images = Storage::disk('s3')
    ->listContents('uploads', true)
    ->filter(FilterBuilder::create()->isImage()->not()->isHidden());
```

### Example: using the facade

```php
use Illuminate\Support\Facades\Storage;
use FlysystemFilter; // Facade alias

Route::get('/filtered-files', function () {
    $listing = Storage::disk('local')->listContents('', true);

    $builder = FlysystemFilter::builder()
        ->isFile()
        ->where(fn ($q) => $q->filenameContains('console')->or()->filenameContains('file'));

    return FlysystemFilter::filter($listing, $builder)
        ->map(fn ($item) => $item->path())
        ->toArray();
});
```

### Example: using a callable

```php
use League\Flysystem\StorageAttributes;

$logs = FlysystemFilter::filter(
    Storage::disk('local')->listContents('', true),
    fn (StorageAttributes $item): bool => str_ends_with($item->path(), '.log'),
);
```

### Example: dependency injection

```php
use Marktaborosi\FlysystemFilter\FlysystemFilter;

final class ReportController
{
    public function __construct(private FlysystemFilter $filter) {}
}
```

## Upgrading from 3.x

Most applications can upgrade without code changes. See [UPGRADE.md](UPGRADE.md) for the details and [CHANGELOG.md](CHANGELOG.md) for every change.

## Development

```bash
composer test       # run the tests (PHPUnit)
composer analyse    # static analysis (PHPStan, level max)
composer cs         # check coding style (PHP-CS-Fixer); composer cs:fix to fix it
composer rector     # check for automated refactorings (Rector)
composer check      # run all of the above
```

## Contributing

Feel free to contribute by submitting issues or pull requests on [GitHub](https://github.com/marktaborosi/flysystem-filter).

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

> Made with ❤️ by [Mark Taborosi](https://github.com/marktaborosi)
