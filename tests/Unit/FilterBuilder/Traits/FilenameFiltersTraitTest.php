<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Closure;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FilenameFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, string, bool}>
     */
    public static function cases(): iterable
    {
        yield 'equals ignores extension' => [static fn(FilterBuilder $f) => $f->filenameEquals('system-error'), 'logs/system-error.log', true];
        yield 'equals list' => [static fn(FilterBuilder $f) => $f->filenameEquals(['readme', 'invoice']), 'docs/invoice.pdf', true];
        yield 'equals no match' => [static fn(FilterBuilder $f) => $f->filenameEquals('readme'), 'docs/readme2.md', false];
        yield 'equals int value' => [static fn(FilterBuilder $f) => $f->filenameEquals('2024'), 'reports/2024.csv', true];
        yield 'equals multi-dot' => [static fn(FilterBuilder $f) => $f->filenameEquals('archive.tar'), 'archive.tar.gz', true];
        yield 'equals ignore case' => [static fn(FilterBuilder $f) => $f->filenameEquals('README', ignoreCase: true), 'readme.md', true];
        yield 'not equals' => [static fn(FilterBuilder $f) => $f->filenameNotEquals('readme'), 'docs/license.md', true];
        yield 'not equals excluded' => [static fn(FilterBuilder $f) => $f->filenameNotEquals(['readme', 'license']), 'docs/license.md', false];
        yield 'contains' => [static fn(FilterBuilder $f) => $f->filenameContains(['err', 'warn']), 'system-error.log', true];
        yield 'contains does not look at extension' => [static fn(FilterBuilder $f) => $f->filenameContains('log'), 'system.log', false];
        yield 'not contains' => [static fn(FilterBuilder $f) => $f->filenameNotContains('tmp'), 'report.csv', true];
        yield 'not contains excluded' => [static fn(FilterBuilder $f) => $f->filenameNotContains(['tmp', 'bak']), 'report_bak.csv', false];
        yield 'starts with' => [static fn(FilterBuilder $f) => $f->filenameStartsWith(['report_', 'log_']), 'x/log_2025.txt', true];
        yield 'ends with' => [static fn(FilterBuilder $f) => $f->filenameEndsWith('_final'), 'x/doc_final.pdf', true];
        yield 'ends with does not look at extension' => [static fn(FilterBuilder $f) => $f->filenameEndsWith('.pdf'), 'x/doc.pdf', false];
        yield 'regex' => [static fn(FilterBuilder $f) => $f->filenameMatchesRegex('/^report-\d{4}$/'), 'report-2025.csv', true];
        yield 'regex no match' => [static fn(FilterBuilder $f) => $f->filenameMatchesRegex('/^report-\d{4}$/'), 'report-25.csv', false];
        yield 'glob' => [static fn(FilterBuilder $f) => $f->filenameMatchesGlob('report-20??'), 'a/report-2025.csv', true];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function filename_filters(Closure $build, string $path, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches(Items::file($path)));
    }
}
