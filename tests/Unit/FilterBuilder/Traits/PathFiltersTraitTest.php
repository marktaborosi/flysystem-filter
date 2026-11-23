<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Closure;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PathFiltersTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(FilterBuilder): FilterBuilder, string, bool}>
     */
    public static function cases(): iterable
    {
        yield 'equals match' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathEquals('logs/error.log'), 'logs/error.log', true];
        yield 'equals list' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathEquals(['a.txt', 'logs/error.log']), 'logs/error.log', true];
        yield 'equals no match' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathEquals('logs/error.log'), 'logs/error.txt', false];
        yield 'equals is case-sensitive' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathEquals('Logs/Error.log'), 'logs/error.log', false];
        yield 'equals ignore case' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathEquals('Logs/Error.log', ignoreCase: true), 'logs/error.log', true];
        yield 'equals is strict (no numeric juggling)' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathEquals('1e1'), '10', false];
        yield 'not equals' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathNotEquals(['a.txt', 'b.txt']), 'c.txt', true];
        yield 'not equals excluded' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathNotEquals(['a.txt', 'b.txt']), 'b.txt', false];
        yield 'contains' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathContains(['backup', 'archive']), 'data/archive/x.zip', true];
        yield 'contains no match' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathContains('backup'), 'data/x.zip', false];
        yield 'not contains' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathNotContains(['tmp', 'cache']), 'data/x.zip', true];
        yield 'not contains excluded' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathNotContains(['tmp', 'cache']), 'var/cache/x', false];
        yield 'starts with' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathStartsWith(['tmp/', 'uploads/']), 'uploads/a.png', true];
        yield 'starts with no match' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathStartsWith('tmp/'), 'data/tmp/a', false];
        yield 'ends with' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathEndsWith(['.log', '_backup.json']), 'x/db_backup.json', true];
        yield 'regex' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesRegex('#^logs/\d{4}/.+\.log$#'), 'logs/2025/app.log', true];
        yield 'regex no match' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesRegex('#^logs/\d{4}/#'), 'logs/old/app.log', false];
        yield 'regex list' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesRegex(['/\.md$/', '/\.txt$/']), 'a/b.txt', true];
        yield 'glob star stays in segment' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesGlob('logs/*.log'), 'logs/2025/app.log', false];
        yield 'glob double star' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesGlob('logs/**.log'), 'logs/2025/01/app.log', true];
        yield 'glob braces' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesGlob('**/*.{jpg,png}'), 'a/b/c.png', true];
        yield 'glob double star slash matches zero dirs' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesGlob('**/*.png'), 'c.png', true];
        yield 'glob ignore case' => [static fn(FilterBuilder $f): FilterBuilder => $f->pathMatchesGlob('*.PNG', ignoreCase: true), 'c.png', true];
        yield 'in directory (direct child)' => [static fn(FilterBuilder $f): FilterBuilder => $f->inDirectory('logs'), 'logs/a.log', true];
        yield 'in directory excludes nested' => [static fn(FilterBuilder $f): FilterBuilder => $f->inDirectory('logs'), 'logs/2025/a.log', false];
        yield 'in directory excludes prefix lookalike' => [static fn(FilterBuilder $f): FilterBuilder => $f->inDirectory('logs', recursive: true), 'logs-old/a.log', false];
        yield 'in directory recursive' => [static fn(FilterBuilder $f): FilterBuilder => $f->inDirectory('/logs/', recursive: true), 'logs/2025/a.log', true];
        yield 'in root directory' => [static fn(FilterBuilder $f): FilterBuilder => $f->inDirectory(''), 'a.log', true];
        yield 'in root directory excludes nested' => [static fn(FilterBuilder $f): FilterBuilder => $f->inDirectory(''), 'x/a.log', false];
        yield 'in root recursive matches everything' => [static fn(FilterBuilder $f): FilterBuilder => $f->inDirectory('', recursive: true), 'x/y/a.log', true];
    }

    /**
     * @param Closure(FilterBuilder): FilterBuilder $build
     */
    #[Test]
    #[DataProvider('cases')]
    public function path_filters(Closure $build, string $path, bool $expected): void
    {
        self::assertSame($expected, $build(new FilterBuilder())->matches(Items::file($path)));
    }

    #[Test]
    public function invalid_regex_is_rejected_when_the_filter_is_built(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regular expression "/[unclosed/"');

        (new FilterBuilder())->pathMatchesRegex('/[unclosed/');
    }

    #[Test]
    public function invalid_glob_is_rejected_when_the_filter_is_built(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unclosed "{"');

        (new FilterBuilder())->pathMatchesGlob('*.{jpg,png');
    }
}
