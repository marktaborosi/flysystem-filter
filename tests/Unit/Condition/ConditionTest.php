<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\Condition;

use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Marktaborosi\FlysystemFilter\Condition\AllOf;
use Marktaborosi\FlysystemFilter\Condition\AnyOf;
use Marktaborosi\FlysystemFilter\Condition\Comparison;
use Marktaborosi\FlysystemFilter\Condition\IsDirectory;
use Marktaborosi\FlysystemFilter\Condition\IsFile;
use Marktaborosi\FlysystemFilter\Condition\Not;
use Marktaborosi\FlysystemFilter\Condition\SizeCondition;
use Marktaborosi\FlysystemFilter\Condition\StringCondition;
use Marktaborosi\FlysystemFilter\Condition\StringOperator;
use Marktaborosi\FlysystemFilter\Condition\Subject;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConditionTest extends TestCase
{
    #[Test]
    public function conditions_can_be_composed_without_the_builder(): void
    {
        $condition = new AnyOf(
            new AllOf(new IsFile(), new SizeCondition(Comparison::GreaterThan, 10)),
            new Not(new StringCondition(Subject::Path, StringOperator::StartsWith, 'tmp/')),
        );

        self::assertTrue($condition->matches(Items::file('a', 11)));
        self::assertTrue($condition->matches(Items::dir('docs')));
        self::assertFalse($condition->matches(Items::dir('tmp/x')));
        self::assertSame('is file AND size > 10 B OR NOT path starts with tmp/', $condition->describe());

        $filtered = (new FlysystemFilter())->filter([Items::dir('tmp/x'), Items::file('a', 11)], $condition);
        self::assertCount(1, $filtered->toArray());
    }

    #[Test]
    public function empty_composites(): void
    {
        self::assertTrue((new AllOf())->matches(Items::file('a')));
        self::assertFalse((new AnyOf())->matches(Items::file('a')));
        self::assertSame('everything', (new AllOf())->describe());
        self::assertSame('nothing', (new AnyOf())->describe());
    }

    #[Test]
    public function describe_uses_parentheses_only_where_needed(): void
    {
        self::assertSame('is file AND (is directory OR is file)', (new AllOf(new IsFile(), new AnyOf(new IsDirectory(), new IsFile())))->describe());
        self::assertSame('NOT (is file AND is directory)', (new Not(new AllOf(new IsFile(), new IsDirectory())))->describe());
    }

    #[Test]
    public function comparisons(): void
    {
        self::assertTrue(Comparison::Equal->compare(1, 1));
        self::assertTrue(Comparison::NotEqual->compare(1, 2));
        self::assertTrue(Comparison::GreaterThan->compare(2, 1));
        self::assertTrue(Comparison::GreaterThanOrEqual->compare(1, 1));
        self::assertTrue(Comparison::LessThan->compare(1, 2));
        self::assertTrue(Comparison::LessThanOrEqual->compare(2, 2));
        self::assertFalse(Comparison::LessThan->compare(2, 2));
    }

    #[Test]
    public function subjects_resolve_path_parts(): void
    {
        $item = Items::file('docs/report.final.pdf', visibility: 'private');
        $detector = new ExtensionMimeTypeDetector();

        self::assertSame('docs/report.final.pdf', Subject::Path->resolve($item, $detector));
        self::assertSame('docs', Subject::Dirname->resolve($item, $detector));
        self::assertSame('', Subject::Dirname->resolve(Items::file('a.txt'), $detector));
        self::assertSame('report.final.pdf', Subject::Basename->resolve($item, $detector));
        self::assertSame('report.final', Subject::Filename->resolve($item, $detector));
        self::assertSame('pdf', Subject::Extension->resolve($item, $detector));
        self::assertSame('application/pdf', Subject::MimeType->resolve($item, $detector));
        self::assertSame('private', Subject::Visibility->resolve($item, $detector));
        self::assertNull(Subject::Extension->resolve(Items::file('LICENSE'), $detector));
    }
}
