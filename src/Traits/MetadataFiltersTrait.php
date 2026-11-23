<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\HasMetadata;
use Marktaborosi\FlysystemFilter\Condition\MetadataEquals;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on adapter specific extra metadata (`$item->extraMetadata()`),
 * e.g. `ETag` or `StorageClass` from the AWS S3 adapter.
 *
 * @internal used by {@see FilterBuilder}
 */
trait MetadataFiltersTrait
{
    /**
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    /**
     * The extra metadata contains the key.
     */
    public function hasMetadata(string $key): static
    {
        return $this->push(new HasMetadata($key));
    }

    /**
     * The extra metadata value under `$key` is strictly equal (`===`) to `$value`.
     */
    public function metadataEquals(string $key, mixed $value): static
    {
        return $this->push(new MetadataEquals($key, [$value]));
    }

    /**
     * The extra metadata value under `$key` is strictly equal to one of the values.
     *
     * @param list<mixed> $values
     */
    public function metadataIn(string $key, array $values): static
    {
        return $this->push(new MetadataEquals($key, $values));
    }
}
