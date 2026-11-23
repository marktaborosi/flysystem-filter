<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Closure;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BasenameFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, string, bool}>
     */
    public static function cases(): iterable
    {
        yield 'equals' => [static fn(FilterBuilder $f) => $f->basenameEquals('file.txt'), 'docs/file.txt', true];
        yield 'equals list' => [static fn(FilterBuilder $f) => $f->basenameEquals(['a.csv', 'file.txt']), 'file.txt', true];
        yield 'equals needs extension' => [static fn(FilterBuilder $f) => $f->basenameEquals('file'), 'docs/file.txt', false];
        yield 'not equals' => [static fn(FilterBuilder $f) => $f->basenameNotEquals('a.txt'), 'b.txt', true];
        yield 'not equals excluded' => [static fn(FilterBuilder $f) => $f->basenameNotEquals(['a.txt', 'b.txt']), 'x/b.txt', false];
        yield 'contains' => [static fn(FilterBuilder $f) => $f->basenameContains(['.tar', 'zip']), 'backup.tar.gz', true];
        yield 'not contains' => [static fn(FilterBuilder $f) => $f->basenameNotContains(['~', '.swp']), 'notes.txt', true];
        yield 'not contains excluded' => [static fn(FilterBuilder $f) => $f->basenameNotContains(['~', '.swp']), '.notes.txt.swp', false];
        yield 'starts with' => [static fn(FilterBuilder $f) => $f->basenameStartsWith('log_'), 'x/log_1.txt', true];
        yield 'ends with' => [static fn(FilterBuilder $f) => $f->basenameEndsWith(['.tar.gz', '.zip']), 'x/a.tar.gz', true];
        yield 'ends with no match' => [static fn(FilterBuilder $f) => $f->basenameEndsWith('.php'), 'skip/file.txt', false];
        yield 'ends with ignore case' => [static fn(FilterBuilder $f) => $f->basenameEndsWith('.JPG', ignoreCase: true), 'IMG_1.jpg', true];
        yield 'regex' => [static fn(FilterBuilder $f) => $f->basenameMatchesRegex('/^IMG_\d+\.jpe?g$/i'), 'dcim/img_0042.JPG', true];
        yield 'glob' => [static fn(FilterBuilder $f) => $f->basenameMatchesGlob('*.{jpg,png}'), 'a/b/c.png', true];
        yield 'glob character class' => [static fn(FilterBuilder $f) => $f->basenameMatchesGlob('file[0-9].txt'), 'file7.txt', true];
        yield 'glob negated character class' => [static fn(FilterBuilder $f) => $f->basenameMatchesGlob('file[!0-9].txt'), 'file7.txt', false];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function basename_filters(Closure $build, string $path, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches(Items::file($path)));
    }
}
