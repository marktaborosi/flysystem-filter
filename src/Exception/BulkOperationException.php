<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Exception;

use Marktaborosi\FlysystemFilter\Bulk\BulkResult;
use RuntimeException;
use Throwable;

/**
 * Thrown by {@see BulkResult::throw()} when at least one file of a bulk operation failed.
 * The full result, including the processed files, is available on {@see $result}.
 */
final class BulkOperationException extends RuntimeException implements FilterException
{
    public function __construct(string $message, public readonly BulkResult $result, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function fromResult(BulkResult $result): self
    {
        $first = $result->failed[0] ?? null;

        return new self(
            \sprintf(
                'Bulk %s failed for %d file(s)%s.',
                $result->operation,
                \count($result->failed),
                $first === null ? '' : \sprintf(', first: "%s": %s', $first['from'], $first['error']->getMessage()),
            ),
            $result,
            $first['error'] ?? null,
        );
    }
}
