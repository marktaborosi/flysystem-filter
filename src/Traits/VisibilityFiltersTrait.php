<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Traits;

use League\Flysystem\Visibility;
use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\IsKnown;
use Marktaborosi\FlysystemFilter\Condition\Not;
use Marktaborosi\FlysystemFilter\Condition\StringCondition;
use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Filters on the visibility reported by the adapter (`public`, `private` or adapter specific).
 * Items without a known visibility never match the positive filters.
 *
 * @internal used by {@see FilterBuilder}
 */
trait VisibilityFiltersTrait
{
    /**
     * @param string|int|list<string|int> $values
     *
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function addStringCondition(
        Subject $subject,
        StringOperator $operator,
        string|int|array $values,
        bool $ignoreCase = false,
        bool $negate = false,
    ): static;

    /**
     * @internal implemented by {@see FilterBuilder}
     */
    abstract protected function push(Condition $condition): static;

    /**
     * Visibility is `public`.
     */
    public function isPublic(): static
    {
        return $this->addStringCondition(Subject::Visibility, StringOperator::Equals, Visibility::PUBLIC);
    }

    /**
     * Visibility is `private`.
     */
    public function isPrivate(): static
    {
        return $this->addStringCondition(Subject::Visibility, StringOperator::Equals, Visibility::PRIVATE);
    }

    /**
     * Visibility is exactly one of the values (useful for custom adapter visibilities).
     *
     * @param string|list<string> $visibilities
     */
    public function visibilityEquals(string|array $visibilities): static
    {
        return $this->addStringCondition(Subject::Visibility, StringOperator::Equals, $visibilities);
    }

    /**
     * Visibility is known and none of the values.
     *
     * @param string|list<string> $visibilities
     */
    public function visibilityNotEquals(string|array $visibilities): static
    {
        return $this->push(new AllOf(
            new IsKnown(Subject::Visibility),
            new Not(new StringCondition(Subject::Visibility, StringOperator::Equals, $visibilities)),
        ));
    }

    /**
     * Visibility contains one of the substrings.
     *
     * @param string|list<string> $substrings
     */
    public function visibilityContains(string|array $substrings): static
    {
        return $this->addStringCondition(Subject::Visibility, StringOperator::Contains, $substrings);
    }
}
