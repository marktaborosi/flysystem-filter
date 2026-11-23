<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use Closure;
use League\Flysystem\StorageAttributes;

/**
 * Wraps a user supplied predicate. Not serializable.
 */
final readonly class Callback implements Condition
{
    /** @var Closure(StorageAttributes): mixed */
    private Closure $callback;

    /**
     * @param callable(StorageAttributes): mixed $callback the result is interpreted as a boolean
     */
    public function __construct(callable $callback, private string $description = 'custom callback')
    {
        $this->callback = $callback(...);
    }

    public function matches(StorageAttributes $item): bool
    {
        return (bool) ($this->callback)($item);
    }

    public function describe(): string
    {
        return $this->description;
    }
}
