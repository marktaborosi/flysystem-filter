<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Logic;

use Closure;
use LogicException;
use Marktaborosi\FlysystemFilter\Exception\FilterException;
use Marktaborosi\FlysystemFilter\Exception\InvalidFilterException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Malformed expressions crashed with a ParseError inside eval() in v1–v3.
 * They now throw an InvalidFilterException with a clear message.
 */
final class ValidationTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): mixed, string}>
     */
    public static function malformedAtCallTime(): iterable
    {
        yield 'leading or()' => [static fn(FilterBuilder $f): FilterBuilder => $f->or(), 'at the start of the filter'];
        yield 'leading and()' => [static fn(FilterBuilder $f): FilterBuilder => $f->and(), 'at the start of the filter'];
        yield 'or() or()' => [static fn(FilterBuilder $f): FilterBuilder => $f->isFile()->or()->or(), 'directly after or()'];
        yield 'and() or()' => [static fn(FilterBuilder $f): FilterBuilder => $f->isFile()->and()->or(), 'directly after and()'];
        yield 'or() at group start' => [static fn(FilterBuilder $f): FilterBuilder => $f->isFile()->groupStart()->or(), 'at the start of a group'];
        yield 'not() not()' => [static fn(FilterBuilder $f): FilterBuilder => $f->not()->not(), 'twice in a row'];
        yield 'not() or()' => [static fn(FilterBuilder $f): FilterBuilder => $f->isFile()->not()->or(), 'directly after not()'];
        yield 'empty group' => [static fn(FilterBuilder $f): FilterBuilder => $f->groupStart()->groupEnd(), 'Empty group'];
        yield 'groupEnd() without groupStart()' => [static fn(FilterBuilder $f): FilterBuilder => $f->groupEnd(), 'Mismatched groupEnd()'];
        yield 'group ending with or()' => [static fn(FilterBuilder $f): FilterBuilder => $f->groupStart()->isFile()->or()->groupEnd(), 'Dangling or() before groupEnd()'];
        yield 'group ending with not()' => [static fn(FilterBuilder $f): FilterBuilder => $f->groupStart()->isFile()->not()->groupEnd(), 'Dangling not() before groupEnd()'];
    }

    /**
     * @param Closure(FilterBuilder): mixed $build
     */
    #[Test]
    #[DataProvider('malformedAtCallTime')]
    public function malformed_chains_throw_at_the_offending_call(Closure $build, string $message): void
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage($message);

        $build(new FilterBuilder());
    }

    /**
     * @return iterable<string, array{Closure(FilterBuilder): mixed, string}>
     */
    public static function incompleteExpressions(): iterable
    {
        yield 'trailing and()' => [static fn(FilterBuilder $f): FilterBuilder => $f->isFile()->and(), 'Dangling and() before the end of the filter'];
        yield 'trailing or()' => [static fn(FilterBuilder $f): FilterBuilder => $f->isFile()->or(), 'Dangling or() before the end of the filter'];
        yield 'trailing not()' => [static fn(FilterBuilder $f): FilterBuilder => $f->isFile()->not(), 'Dangling not() before the end of the filter'];
        yield 'unclosed group' => [static fn(FilterBuilder $f): FilterBuilder => $f->groupStart()->extensionEquals('txt'), 'Unbalanced groups: missing groupEnd() for 1 open group(s).'];
        yield 'two unclosed groups' => [static fn(FilterBuilder $f): FilterBuilder => $f->groupStart()->isFile()->groupStart()->isPublic(), 'for 2 open group(s)'];
    }

    /**
     * @param Closure(FilterBuilder): mixed $build
     */
    #[Test]
    #[DataProvider('incompleteExpressions')]
    public function incomplete_expressions_throw_when_evaluated(Closure $build, string $message): void
    {
        $filter = new FilterBuilder();
        $build($filter);

        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage($message);

        $filter->matches(Items::file('a.txt'));
    }

    #[Test]
    public function exceptions_stay_backwards_compatible_logic_exceptions(): void
    {
        try {
            (new FilterBuilder())->groupEnd();
            self::fail('Expected an exception.');
        } catch (LogicException $exception) {
            self::assertInstanceOf(FilterException::class, $exception);
        }
    }

    #[Test]
    public function a_failed_call_does_not_corrupt_the_builder(): void
    {
        $filter = (new FilterBuilder())->isFile()->or();

        try {
            $filter->or();
        } catch (InvalidFilterException) {
        }

        $filter->isDirectory();

        self::assertTrue($filter->matches(Items::dir('docs')));
        self::assertTrue($filter->matches(Items::file('a.txt')));
    }
}
