
# Changelog

All notable changes to **Flysystem Filter** will be documented in this file.  
This project adheres to [Semantic Versioning](https://semver.org).

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
