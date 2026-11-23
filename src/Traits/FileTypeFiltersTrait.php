<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\IsDirectory;
use Marktaborosi\FlysystemFilter\Condition\IsFile;
use Marktaborosi\FlysystemFilter\Condition\IsHidden;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the kind of entry.
 *
 * @internal used by {@see FilterBuilder}
 */
trait FileTypeFiltersTrait
{
    /**
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    /**
     * File entries.
     */
    public function isFile(): static
    {
        return $this->push(new IsFile());
    }

    /**
     * Directory entries.
     */
    public function isDirectory(): static
    {
        return $this->push(new IsDirectory());
    }

    /**
     * Dot entries (`.env`, `.git`) and everything inside dot directories (`.git/config`).
     * Use `->not()->isHidden()` to skip them.
     */
    public function isHidden(): static
    {
        return $this->push(new IsHidden());
    }
}
