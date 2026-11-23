<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Laravel;

use BadMethodCallException;
use Closure;
use Countable;
use DateInterval;
use DateTimeInterface;
use Generator;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\LazyCollection;
use IteratorAggregate;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use ReflectionMethod;
use Stringable;
use Traversable;

/**
 * A filter query on a Laravel disk, returned by the `Storage::disk(...)->filter()` macro.
 *
 * Every {@see FilterBuilder} method can be chained on it; the query runs when a terminal
 * method is called: {@see get()}, {@see paths()}, {@see first()}, {@see count()},
 * {@see exists()} or by iterating it.
 *
 * ```php
 * $paths = Storage::disk('s3')
 *     ->filter('uploads')
 *     ->isImage()
 *     ->sizeGt('5M')
 *     ->paths();
 * ```
 *
 * Results are lazy: the disk is listed only when the results are consumed, and every
 * terminal call lists it again, so the query object can be reused.
 *
 * @implements IteratorAggregate<int, StorageAttributes>
 *
 * @method self and()
 * @method self or()
 * @method self not()
 * @method self groupStart()
 * @method self groupEnd()
 * @method self group_start()
 * @method self group_end()
 * @method self where(Condition|Closure $condition)
 * @method self orWhere(Condition|Closure $condition)
 * @method self whereNot(Condition|Closure $condition)
 * @method self orWhereNot(Condition|Closure $condition)
 * @method self whereAny(iterable<Condition|Closure> $conditions)
 * @method self whereAll(iterable<Condition|Closure> $conditions)
 * @method self when(mixed $value, Closure $callback, ?Closure $default = null)
 * @method self unless(mixed $value, Closure $callback, ?Closure $default = null)
 * @method self custom(callable $callback, ?string $description = null)
 * @method self isFile()
 * @method self isDirectory()
 * @method self isHidden()
 * @method self pathEquals(string|list<string> $paths, bool $ignoreCase = false)
 * @method self pathNotEquals(string|list<string> $paths, bool $ignoreCase = false)
 * @method self pathContains(string|list<string> $substrings, bool $ignoreCase = false)
 * @method self pathNotContains(string|list<string> $substrings, bool $ignoreCase = false)
 * @method self pathStartsWith(string|list<string> $prefixes, bool $ignoreCase = false)
 * @method self pathEndsWith(string|list<string> $suffixes, bool $ignoreCase = false)
 * @method self pathMatchesRegex(string|list<string> $patterns)
 * @method self pathMatchesGlob(string|list<string> $patterns, bool $ignoreCase = false)
 * @method self inDirectory(string|list<string> $directories, bool $recursive = false)
 * @method self filenameEquals(string|list<string> $filenames, bool $ignoreCase = false)
 * @method self filenameNotEquals(string|list<string> $filenames, bool $ignoreCase = false)
 * @method self filenameContains(string|list<string> $substrings, bool $ignoreCase = false)
 * @method self filenameNotContains(string|list<string> $substrings, bool $ignoreCase = false)
 * @method self filenameStartsWith(string|list<string> $prefixes, bool $ignoreCase = false)
 * @method self filenameEndsWith(string|list<string> $suffixes, bool $ignoreCase = false)
 * @method self filenameMatchesRegex(string|list<string> $patterns)
 * @method self filenameMatchesGlob(string|list<string> $patterns, bool $ignoreCase = false)
 * @method self basenameEquals(string|list<string> $basenames, bool $ignoreCase = false)
 * @method self basenameNotEquals(string|list<string> $basenames, bool $ignoreCase = false)
 * @method self basenameContains(string|list<string> $substrings, bool $ignoreCase = false)
 * @method self basenameNotContains(string|list<string> $substrings, bool $ignoreCase = false)
 * @method self basenameStartsWith(string|list<string> $prefixes, bool $ignoreCase = false)
 * @method self basenameEndsWith(string|list<string> $suffixes, bool $ignoreCase = false)
 * @method self basenameMatchesRegex(string|list<string> $patterns)
 * @method self basenameMatchesGlob(string|list<string> $patterns, bool $ignoreCase = false)
 * @method self extensionEquals(string|list<string> $extensions, bool $ignoreCase = true)
 * @method self extensionNotEquals(string|list<string> $extensions, bool $ignoreCase = true)
 * @method self extensionContains(string|list<string> $substrings, bool $ignoreCase = true)
 * @method self extensionNotContains(string|list<string> $substrings, bool $ignoreCase = true)
 * @method self hasExtension()
 * @method self mimeTypeEquals(string|list<string> $mimeTypes)
 * @method self mimeTypeNotEquals(string|list<string> $mimeTypes)
 * @method self mimeTypeContains(string|list<string> $substrings)
 * @method self mimeTypeNotContains(string|list<string> $substrings)
 * @method self mimeTypeMatches(string|list<string> $patterns)
 * @method self isImage()
 * @method self isVideo()
 * @method self isAudio()
 * @method self isText()
 * @method self sizeEquals(int|string $size)
 * @method self sizeNotEquals(int|string $size)
 * @method self sizeGt(int|string $size)
 * @method self sizeGte(int|string $size)
 * @method self sizeLt(int|string $size)
 * @method self sizeLte(int|string $size)
 * @method self sizeBetween(int|string $min, int|string $max)
 * @method self isEmptyFile()
 * @method self lastModifiedBefore(int|string|DateTimeInterface $date)
 * @method self lastModifiedAfter(int|string|DateTimeInterface $date)
 * @method self lastModifiedBetween(int|string|DateTimeInterface $start, int|string|DateTimeInterface $end)
 * @method self lastModifiedWithin(DateInterval|string $interval, ?DateTimeInterface $now = null)
 * @method self lastModifiedOlderThan(DateInterval|string $interval, ?DateTimeInterface $now = null)
 * @method self isPublic()
 * @method self isPrivate()
 * @method self visibilityEquals(string|list<string> $visibilities)
 * @method self visibilityNotEquals(string|list<string> $visibilities)
 * @method self visibilityContains(string|list<string> $substrings)
 * @method self depthEquals(int $depth)
 * @method self depthGt(int $depth)
 * @method self depthGte(int $depth)
 * @method self depthLt(int $depth)
 * @method self depthLte(int $depth)
 * @method self depthBetween(int $min, int $max)
 * @method self hasMetadata(string $key)
 * @method self metadataEquals(string $key, mixed $value)
 * @method self metadataIn(string $key, list<mixed> $values)
 * @method bool hasConditions()
 * @method string describe()
 * @method Condition toCondition()
 */
final readonly class StorageFilter implements IteratorAggregate, Countable, Stringable
{
    private FilterBuilder $builder;

    /**
     * @param string $directory directory to list, relative to the disk root (`''` = root)
     * @param bool $recursive whether to include sub-directories
     * @param FilterBuilder|null $builder an existing filter to start from (it is copied, not modified)
     */
    public function __construct(
        private FilesystemOperator $filesystem,
        private string $directory = '',
        private bool $recursive = true,
        ?FilterBuilder $builder = null,
    ) {
        $this->builder = $builder instanceof FilterBuilder ? clone $builder : new FilterBuilder();
    }

    /**
     * Typed alternative to the macro, e.g. for static analysis:
     * `StorageFilter::on(Storage::disk('s3'), 'uploads')->isImage()->paths()`.
     */
    public static function on(
        FilesystemAdapter|FilesystemOperator $disk,
        string $directory = '',
        bool $recursive = true,
        ?FilterBuilder $builder = null,
    ): self {
        return new self(
            $disk instanceof FilesystemAdapter ? $disk->getDriver() : $disk,
            $directory,
            $recursive,
            $builder,
        );
    }

    /**
     * Forwards filter methods (and FilterBuilder macros) to the underlying builder.
     *
     * @param array<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        $forwardable = !str_starts_with($method, '__')
            && (method_exists($this->builder, $method) || FilterBuilder::hasMacro($method));

        if (!$forwardable) {
            throw new BadMethodCallException(\sprintf('Method %s::%s() does not exist.', self::class, $method));
        }

        $result = method_exists($this->builder, $method)
            ? (new ReflectionMethod($this->builder, $method))->invokeArgs($this->builder, $arguments)
            : $this->builder->__call($method, $arguments);

        return $result === $this->builder ? $this : $result;
    }

    /**
     * The filter being built, e.g. to reuse it elsewhere or to export it with `toArray()`.
     */
    public function builder(): FilterBuilder
    {
        return $this->builder;
    }

    /**
     * The matching items as a lazy Flysystem listing (single pass).
     *
     * @return DirectoryListing<StorageAttributes>
     */
    public function listing(): DirectoryListing
    {
        return (new FlysystemFilter())->filter(
            $this->filesystem->listContents($this->directory, $this->recursive),
            $this->builder,
        );
    }

    /**
     * The matching items (files and directories) as a lazy collection.
     *
     * @return LazyCollection<int, StorageAttributes>
     */
    public function get(): LazyCollection
    {
        $this->builder->toCondition(); // fail fast on an incomplete filter

        /** @return Generator<int, StorageAttributes, mixed, void> */
        $items = function (): Generator {
            yield from $this->listing();
        };

        return new LazyCollection($items);
    }

    /**
     * The paths of the matching items, relative to the disk root.
     *
     * @return LazyCollection<int, string>
     */
    public function paths(): LazyCollection
    {
        return $this->get()->map(static fn(StorageAttributes $item): string => $item->path());
    }

    /**
     * The first matching item; stops listing as soon as it is found.
     */
    public function first(): ?StorageAttributes
    {
        return $this->get()->first();
    }

    /**
     * Whether at least one item matches; stops listing as soon as one is found.
     */
    public function exists(): bool
    {
        return $this->first() instanceof StorageAttributes;
    }

    /**
     * Whether no item matches.
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * The number of matching items.
     */
    public function count(): int
    {
        $count = 0;

        foreach ($this->get() as $ignored) {
            ++$count;
        }

        return $count;
    }

    /**
     * @return Traversable<int, StorageAttributes>
     */
    public function getIterator(): Traversable
    {
        return $this->get()->getIterator();
    }

    public function __toString(): string
    {
        return $this->builder->describe();
    }
}
