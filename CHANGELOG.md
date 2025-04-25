
# Changelog

All notable changes to **Flysystem Filter** will be documented in this file.  
This project adheres to [Semantic Versioning](https://semver.org).

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
