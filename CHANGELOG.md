# Changelog

All notable changes to **Flysystem Filter** will be documented in this file.  
This project adheres to [Semantic Versioning](https://semver.org).

## [4.1.0] – Unreleased

### Added
- **Laravel disk macro:** `Storage::disk('s3')->filter('uploads')` (or `Storage::filter()` on the default disk) returns a `StorageFilter` query. Every `FilterBuilder` method can be chained on it. Results come from `paths()`, `get()`, `first()`, `exists()`, `doesntExist()`, `count()`, or by iterating the query.
  ```php
  Storage::disk('s3')->filter('uploads')->isImage()->sizeGt('5M')->paths();
  ```
- Results are lazy `LazyCollection`s, so `first()` and `exists()` stop listing at the first match. Queries can be reused.
- `filter($directory, $recursive = true, $builder = null)` accepts an existing `FilterBuilder` to start from. The builder is copied, not modified.
- `StorageFilter::on($disk, …)`: a fully typed alternative to the macro for IDEs and static analysis without Larastan. It works with any Flysystem `FilesystemOperator`, too.
- `FilterBuilder` macros can be used on `StorageFilter` queries as well.

### Changed
- The service provider is no longer deferred, because it registers the disk macro when the application boots. An existing `filter` macro registered by another package is left untouched.

---

## [4.0.0] – 2026-11-23

A rewrite of the evaluation core and a much smarter builder. See [UPGRADE.md](UPGRADE.md) for a step-by-step guide.

### ⚠️ Breaking Changes
- **Dependencies**: `illuminate/support` and `nesbot/carbon` are no longer required. The package only needs `league/flysystem` and `league/mime-type-detection`; the Laravel integration is still auto-discovered when Laravel is installed.
- **`final` classes**: `FilterBuilder`, `FlysystemFilter`, the service provider and the facade are now `final`. Extend through `Condition` classes, `where()`, `custom()` or macros.
- **Malformed expressions** (`->or()->or()`, leading or trailing operators, empty groups, unbalanced groups) throw `InvalidFilterException` (a `LogicException`) with a clear message, mostly at the offending call, instead of a `ParseError` from `eval()`.
- **Invalid arguments** (regex, glob, size, date, interval, inverted ranges) throw `Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException` (a `LogicException`) when the filter is built.
- **Size filters**: files with an unknown size (`fileSize() === null`) never match. Previously `sizeLt()`, `sizeLte()` and `sizeNotEquals()` matched them, because `null < 1024` is `true` in PHP.
- **Extension filters** ignore directories (`conf.d` has no extension).
- **MIME type filters**: directories have no MIME type, the adapter's MIME type is preferred over extension detection, and comparisons are case-insensitive.
- **Exact-match filters** (`pathEquals`, `filenameEquals`, `basenameEquals`, …) compare strictly.
- **Date filters**: the parameter `$timestamp` was renamed to `$date`. This only affects named arguments.
- **Facade accessor** is now the `FlysystemFilter` class; `app('flysystem.filter')` still works as an alias.
- **Traits** in `Marktaborosi\FlysystemFilter\Traits` are marked `@internal`.

### Fixed
- Removed `eval()` from `FilterBuilder::matches()`. Expressions compile into an immutable condition tree with short-circuit evaluation. The semantics are identical: AND binds tighter than OR, and conditions without an operator are AND-ed. A test checks this against PHP's own operators on 2,000 random expressions. Evaluation is about 2.8× faster.
- `strtolower(): Passing null` deprecation for files without an extension.
- `CarbonImmutable`, `DateTimeImmutable` and every other `DateTimeInterface` are accepted by the date filters; previously they caused a `TypeError`.
- An invalid regular expression no longer produces a warning and a silent non-match for every item.
- Removed the duplicate `filterMimeTypeContains()` implementation.
- The facade PHPDoc now documents the `callable` and `Condition` parameter types.
- The `Tests\` namespace is no longer in the production autoloader. Tests, examples and tooling are excluded from the dist archive (`.gitattributes`).

### Added
- **Negation**: `not()`, `whereNot()`, `orWhereNot()`.
- **Closure groups and helpers**: `where(Closure|Condition)`, `orWhere()`, `whereAny()`, `whereAll()`, `when()`, `unless()`.
- **camelCase grouping**: `groupStart()` / `groupEnd()`.
- **Native Flysystem usage**: `FilterBuilder` is invokable, so `$listing->filter($builder)` works.
- **Globs**: `pathMatchesGlob()`, `basenameMatchesGlob()`, `filenameMatchesGlob()` and `mimeTypeMatches()`, supporting `*`, `**`, `?`, `[…]` and `{a,b}`.
- **Directories**: `inDirectory($dir, recursive: bool)`.
- **New filters**: `isHidden()`, `isEmptyFile()`, `hasExtension()`, `basenameMatchesRegex()`, `visibilityNotEquals()`, `depthGte()`, `depthLte()`, `depthBetween()`, `isImage()`, `isVideo()`, `isAudio()`, `isText()`, `hasMetadata()`, `metadataEquals()`, `metadataIn()`.
- **Relative dates**: `lastModifiedWithin('7 days')` and `lastModifiedOlderThan('30 days')`. Date filters also accept date strings.
- **Case-insensitive matching**: an `$ignoreCase` argument on the string filters. Extensions and MIME types are case-insensitive by default, and a leading dot in an extension is ignored.
- **Smarter sizes**: `'1.5M'`, `'10KB'`, `'10KiB'`, `'2 GB'`, `'1P'`.
- **Regex lists**: the regex filters accept a list of patterns.
- **Debugging**: `describe()` / `__toString()` return a human-readable form of the filter.
- **Serialization**: `toArray()`, `jsonSerialize()`, `FilterBuilder::fromArray()` and `FilterBuilder::fromJson()`, plus `ConditionSerializer` with custom types and an `allowRegex` switch for untrusted input.
- **Extensibility**: a public `Condition` / `SerializableCondition` API with immutable condition classes (`AllOf`, `AnyOf`, `Not`, `StringCondition`, `SizeCondition`, …), `toCondition()`, and macros (`FilterBuilder::macro()`).
- **MIME detection**: a custom `MimeTypeDetector` can be injected through the constructor.
- **Helpers**: `FilterBuilder::create()`, `hasConditions()`, and `FlysystemFilter::builder()` (also available through the facade).
- **`FlysystemFilter::filter()`** accepts any iterable and a `Condition`, `FilterBuilder` or callable, and validates the filter before iterating.
- **Laravel**: Laravel 11, 12 and 13 are supported. The service provider is deferred, and `FlysystemFilter` can be injected by type.

### Deprecated
- `group_start()` / `group_end()`: use `groupStart()` / `groupEnd()`.

### Documentation
- Rewritten README with a table of contents, a step-by-step guide (combining, grouping, negation, dynamic filters, error handling, debugging) and a full API reference describing every method.
- Updated badges: the license badge now points to `main`, the PHP badge covers 8.2 – 8.5, and there are new badges for the Packagist version, Laravel 11/12/13 support and PHPStan level max.
- New `UPGRADE.md` with a 3.x → 4.0 migration guide.
- New `examples/advanced.php`. The existing examples were updated to the v4 API.
- Fixed the unclosed code block in the 2.0.0 changelog entry.

### Tooling
- PHPStan at level max with strict rules and the PHPUnit extension, analysed for PHP 8.2 – 8.5.
- PHP-CS-Fixer (PER-CS 2.0) and Rector configuration, plus a `composer check` script.
- GitHub Actions matrix: PHP 8.2 – 8.5 × Laravel 11/12/13 × lowest/stable dependencies, plus static analysis and coverage jobs.
- Rewritten test suite: PHPUnit attributes, real Flysystem attribute objects instead of mocks, data providers, and edge-case, serialization, Laravel and filesystem integration tests (300+ tests).

---

## [3.0.0] – 2025-08-15

### ⚠️ Breaking Changes
- **Changed**: `FlysystemFilter::filter()` is no longer a `static` method — it is now an **instance method**.
    - **Why**: This change was made to enable Laravel service container and Facade integration.
    - **Impact**: Any previous calls like
      ```php
      FlysystemFilter::filter($listing, $builder);
      ```  
      must now either:
        - Use the Laravel Facade (if in a Laravel app):
          ```php
          FlysystemFilter::filter($listing, $builder);
          ```
          *(Looks static, but is resolved through the container)*
        - Or instantiate the class directly:
          ```php
          (new FlysystemFilter())->filter($listing, $builder);
          ```

### Added
- ✅ **Laravel Support** 🎉
    - `FlysystemFilterServiceProvider` for service container registration.
    - `FlysystemFilterFacade` for expressive, Facade-style usage in Laravel applications.
    - Added `composer.json` auto-discovery for both the Service Provider and the Facade alias.
- ✅ **Callable Predicate Support**
    - `FlysystemFilter::filter()` now accepts a `callable` in addition to a `FilterBuilder` instance.
    - This allows custom filter logic without building a `FilterBuilder`.  
      Example:
      ```php
      use League\Flysystem\StorageAttributes;

      $callable = static fn(StorageAttributes $item): bool =>
          str_ends_with($item->path(), '.log');

      $filtered = (new FlysystemFilter())->filter($listing, $callable);
      ```
- ✅ Updated usage examples and integration tests for Laravel and callable usage.

### Changed
- 📦 **Dependencies**: Bumped `nesbot/carbon` to the latest stable version.

---

## [2.0.0] – 2025-05-01

### ⚠️ Breaking Changes

- **Changed**: `FlysystemFilter::filter()` now returns a `DirectoryListing` constructed from a generator instead of an array. This change improves memory efficiency but may affect consumers who rely on multiple iterations over the result. To adapt, consider converting the result to an array if needed:

  ```php
  $filtered = FlysystemFilter::filter($listing, $builder);
  $results = iterator_to_array($filtered, false);
  ```

---

## [1.1.0] – 2025-04-25

### Added
- ✅ **MIME type filtering support**:
  - `mimeTypeEquals`, `mimeTypeNotEquals`
  - `mimeTypeContains`, `mimeTypeNotContains`
- ✅ Additional unit tests for all MIME-related conditions.
- ✅ Test files have been **reorganized** into logical groups (`Attributes`, `Logic`, etc).
- ✅ Example files renamed for consistency.

---

## [1.0.2] – 2025-04-25

### Fixed
- 🐛 Fixed missing `K` (kilobyte) support in the `parseSize()` method for size filtering.
- 📦 Patch release according to SemVer.

---

## [1.0.1] – 2024-12-08

### Fixed
- 📦 composer.json updated to ensure `README.md` displays correctly on packagist.org.

---

## [1.0.0] – 2024-12-07

### Initial Release 🎉

This is the first stable release of **Flysystem Filter**, a PHP utility for advanced filtering of Flysystem `DirectoryListing`.

### Features
- 🧱 `FilterBuilder` with fluent, chainable condition building.
- 🔗 Supports logical operators `and()`, `or()` and grouping via `group_start()`, `group_end()`.
- 🔍 Condition types include:
  - File / directory type (`isFile`, `isDirectory`)
  - Path matching (`pathEquals`, `pathContains`, `pathMatchesRegex`)
  - Filename conditions (`filenameEquals`, `filenameNotEquals`, `filenameContains`)
  - Extension matching (`extensionEquals`, `extensionContains`, etc.)
  - File size conditions (`sizeGt`, `sizeLt`, `sizeBetween`)
  - Visibility (`isPublic`, `isPrivate`)
  - Metadata key/value matching

- 🛠️ `FlysystemFilter` class to apply conditions directly to a `DirectoryListing`.

---
