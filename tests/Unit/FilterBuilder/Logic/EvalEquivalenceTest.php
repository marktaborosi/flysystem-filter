<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Logic;

use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Proves that the condition-tree evaluator gives exactly the same result as the
 * `eval()` based evaluator of v1–v3 (i.e. PHP's own `&&`, `||`, `!` and parentheses),
 * including operator precedence and implicit AND, on thousands of random expressions.
 *
 * `eval()` is used here only as the reference oracle.
 */
final class EvalEquivalenceTest extends TestCase
{
    private const CASES_PER_SEED = 400;

    /**
     * @return iterable<string, array{int}>
     */
    public static function seeds(): iterable
    {
        foreach ([1, 7, 42, 1337, 2026] as $seed) {
            yield 'seed ' . $seed => [$seed];
        }
    }

    #[Test]
    #[DataProvider('seeds')]
    public function random_expressions_match_php_boolean_semantics(int $seed): void
    {
        mt_srand($seed);
        $item = Items::file('any.txt');

        for ($case = 0; $case < self::CASES_PER_SEED; ++$case) {
            $builder = new FilterBuilder();
            $expression = $this->expression($builder, depth: 0);

            /** @var bool $expected */
            $expected = eval('return ' . $expression . ';');

            self::assertSame(
                $expected,
                $builder->matches($item),
                \sprintf('Seed %d, case %d: %s  (builder: %s)', $seed, $case, $expression, $builder->describe()),
            );
        }
    }

    /**
     * Adds a random expression to the builder and returns the equivalent PHP expression.
     */
    private function expression(FilterBuilder $builder, int $depth): string
    {
        $php = $this->operand($builder, $depth);
        $operands = mt_rand(0, 4);

        for ($i = 0; $i < $operands; ++$i) {
            $operator = mt_rand(0, 2);

            if ($operator === 0) {
                $builder->or();
                $php .= ' || ';
            } elseif ($operator === 1) {
                $builder->and();
                $php .= ' && ';
            } else {
                // implicit AND
                $php .= ' && ';
            }

            $php .= $this->operand($builder, $depth);
        }

        return $php;
    }

    private function operand(FilterBuilder $builder, int $depth): string
    {
        $negate = mt_rand(0, 4) === 0;
        $prefix = '';

        if ($negate) {
            $builder->not();
            $prefix = '!';
        }

        if ($depth < 3 && mt_rand(0, 3) === 0) {
            $builder->groupStart();
            $inner = $this->expression($builder, $depth + 1);
            $builder->groupEnd();

            return $prefix . '(' . $inner . ')';
        }

        $value = (bool) mt_rand(0, 1);
        $builder->custom(static fn(): bool => $value, $value ? 'T' : 'F');

        return $prefix . ($value ? 'true' : 'false');
    }
}
