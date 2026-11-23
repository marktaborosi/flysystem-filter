# Upgrade guide

## From 3.x to 4.0

Most applications can upgrade without code changes. Go through the list below and check the points that apply to you.

### 1. Update the constraint

```bash
composer require marktaborosi/flysystem-filter:^4.0
```

The package no longer requires `illuminate/support` or `nesbot/carbon`. If your application used them **only** because this package pulled them in, require them yourself.

Laravel 11, 12 and 13 are supported, and auto-discovery works as before.

### 2. Grouping methods (deprecated, still working)

```diff
- ->group_start()->extensionEquals('txt')->or()->extensionEquals('md')->group_end()
+ ->groupStart()->extensionEquals('txt')->or()->extensionEquals('md')->groupEnd()
```

Or use a closure:

```php
->where(fn (FilterBuilder $q) => $q->extensionEquals('txt')->or()->extensionEquals('md'))
```

### 3. Exceptions

| Situation | 3.x | 4.0 |
|---|---|---|
| `->or()->or()`, a leading `or()`, a trailing `and()`, an empty group | `ParseError` from `eval()` while filtering | `InvalidFilterException` (a `LogicException`), usually at the offending call |
| `group_end()` without `group_start()` | `LogicException` | `InvalidFilterException` (a `LogicException`) |
| Unclosed group | `LogicException` while filtering | `InvalidFilterException` (a `LogicException`) while filtering |
| Invalid size string | `LogicException` | `InvalidArgumentException` (a `LogicException`) when the filter is built |
| Invalid regex | Warning + no match for every item | `InvalidArgumentException` when the filter is built |

If you catch `LogicException`, nothing changes. All package exceptions also implement `Marktaborosi\FlysystemFilter\Exception\FilterException`.

### 4. Behaviour changes (bug fixes)

- **Unknown file size:** a file whose size the adapter does not report (`fileSize() === null`) no longer matches `sizeLt()`, `sizeLte()` or `sizeNotEquals()`.
- **Extensions of directories:** `extensionEquals('d')` no longer matches the directory `conf.d`.
- **MIME types:** directories never match MIME filters. When the adapter reports a MIME type, that value is used instead of guessing from the extension. Comparisons are case-insensitive.
- **Strict equality:** `pathEquals()`, `filenameEquals()` and `basenameEquals()` compare strictly, so `'10'` no longer equals `'1e1'`.

### 5. `final` classes

`FilterBuilder`, `FlysystemFilter`, `FlysystemFilterServiceProvider` and `FlysystemFilterFacade` are `final`. If you extended `FilterBuilder` to add filters, use one of these instead:

```php
// a macro
FilterBuilder::macro('isLog', fn (FilterBuilder $q) => $q->extensionEquals('log'));

// a reusable condition class
final class IsLog implements \Marktaborosi\FlysystemFilter\Condition\Condition { /* matches(), describe() */ }
$builder->where(new IsLog());
```

If you used the traits from `Marktaborosi\FlysystemFilter\Traits` in your own classes: they are now internal and rely on `FilterBuilder`.

### 6. Named arguments

The date filters renamed `$timestamp` to `$date`. This only matters if you called them with named arguments.

### 7. Optional: simplify your code

```diff
- $filtered = (new FlysystemFilter())->filter($filesystem->listContents('', true), $builder);
+ $filtered = $filesystem->listContents('', true)->filter($builder);
```
