<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Bulk;

use Countable;
use Marktaborosi\FlysystemFilter\Exception\BulkOperationException;
use Throwable;

/**
 * The outcome of a bulk operation (delete, move, copy, …) on the files matched by a filter.
 *
 * Bulk operations never stop half-way because of a single failing file: every file is
 * attempted, and the result reports what was processed, skipped and failed.
 * Call {@see throw()} to turn failures into an exception.
 */
final readonly class BulkResult implements Countable
{
    /**
     * @param string $operation e.g. `delete`, `move`, `copyTo`
     * @param bool $dryRun whether nothing was actually changed
     * @param list<array{from: string, to: string|null}> $processed files that were (or in a dry run would be) processed
     * @param list<array{from: string, to: string|null, reason: string}> $skipped files that were left alone, with the reason
     * @param list<array{from: string, to: string|null, error: Throwable}> $failed files whose operation threw an exception
     */
    public function __construct(
        public string $operation,
        public bool $dryRun,
        public array $processed = [],
        public array $skipped = [],
        public array $failed = [],
    ) {}

    /**
     * The number of processed files.
     */
    public function count(): int
    {
        return \count($this->processed);
    }

    /**
     * Whether no file failed. Skipped files do not count as failures.
     */
    public function successful(): bool
    {
        return $this->failed === [];
    }

    public function hasFailures(): bool
    {
        return $this->failed !== [];
    }

    /**
     * Source paths of the processed files.
     *
     * @return list<string>
     */
    public function paths(): array
    {
        return array_map(static fn(array $entry): string => $entry['from'], $this->processed);
    }

    /**
     * Source path → target path of the processed files (move and copy operations).
     *
     * @return array<string, string|null>
     */
    public function map(): array
    {
        $map = [];

        foreach ($this->processed as $entry) {
            $map[$entry['from']] = $entry['to'];
        }

        return $map;
    }

    /**
     * Source paths of the skipped files with the reason, e.g. `['a.jpg' => 'target exists']`.
     *
     * @return array<string, string>
     */
    public function skippedPaths(): array
    {
        $skipped = [];

        foreach ($this->skipped as $entry) {
            $skipped[$entry['from']] = $entry['reason'];
        }

        return $skipped;
    }

    /**
     * Source paths of the failed files with the exception.
     *
     * @return array<string, Throwable>
     */
    public function failures(): array
    {
        $failures = [];

        foreach ($this->failed as $entry) {
            $failures[$entry['from']] = $entry['error'];
        }

        return $failures;
    }

    /**
     * Throws a {@see BulkOperationException} if any file failed, otherwise returns the result.
     *
     * @throws BulkOperationException
     */
    public function throw(): self
    {
        if ($this->hasFailures()) {
            throw BulkOperationException::fromResult($this);
        }

        return $this;
    }

    /**
     * A one-line summary, e.g. `move: 12 processed, 1 skipped, 0 failed`.
     */
    public function summary(): string
    {
        return \sprintf(
            '%s%s: %d processed, %d skipped, %d failed',
            $this->operation,
            $this->dryRun ? ' (dry run)' : '',
            \count($this->processed),
            \count($this->skipped),
            \count($this->failed),
        );
    }
}
