<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\Comparison;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\DepthCondition;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the depth of the path: the number of `/` separators after trimming
 * leading/trailing slashes (`file.txt` → 0, `docs/file.txt` → 1, `a/b/c/file.txt` → 3).
 *
 * @internal used by {@see FilterBuilder}
 */
trait DepthFiltersTrait
{
    /**
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    public function depthEquals(int $depth): static
    {
        return $this->push(new DepthCondition(Comparison::Equal, $depth));
    }

    /**
     * Deeper than the depth (exclusive).
     */
    public function depthGt(int $depth): static
    {
        return $this->push(new DepthCondition(Comparison::GreaterThan, $depth));
    }

    /**
     * At least the depth (inclusive).
     */
    public function depthGte(int $depth): static
    {
        return $this->push(new DepthCondition(Comparison::GreaterThanOrEqual, $depth));
    }

    /**
     * Shallower than the depth (exclusive).
     */
    public function depthLt(int $depth): static
    {
        return $this->push(new DepthCondition(Comparison::LessThan, $depth));
    }

    /**
     * At most the depth (inclusive).
     */
    public function depthLte(int $depth): static
    {
        return $this->push(new DepthCondition(Comparison::LessThanOrEqual, $depth));
    }

    /**
     * Within the range, both ends inclusive.
     *
     * @throws InvalidArgumentException when min > max
     */
    public function depthBetween(int $min, int $max): static
    {
        if ($min > $max) {
            throw new InvalidArgumentException(\sprintf('depthBetween(): the minimum (%d) is larger than the maximum (%d).', $min, $max));
        }

        return $this->push(new AllOf(
            new DepthCondition(Comparison::GreaterThanOrEqual, $min),
            new DepthCondition(Comparison::LessThanOrEqual, $max),
        ));
    }
}
