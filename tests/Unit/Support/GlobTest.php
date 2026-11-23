<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\Support;

use Marktaborosi\FlysystemFilter\Support\Glob;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GlobTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function cases(): iterable
    {
        yield 'star' => ['*.txt', 'a.txt', true];
        yield 'star not across slash' => ['*.txt', 'a/b.txt', false];
        yield 'double star across slash' => ['**.txt', 'a/b/c.txt', true];
        yield 'double star slash zero dirs' => ['a/**/b.txt', 'a/b.txt', true];
        yield 'double star slash many dirs' => ['a/**/b.txt', 'a/x/y/b.txt', true];
        yield 'question mark' => ['file?.txt', 'file1.txt', true];
        yield 'question mark not slash' => ['a?b', 'a/b', false];
        yield 'class' => ['[abc].txt', 'b.txt', true];
        yield 'range' => ['[a-c].txt', 'd.txt', false];
        yield 'negated class' => ['[!a-c].txt', 'd.txt', true];
        yield 'caret negated class' => ['[^a-c].txt', 'a.txt', false];
        yield 'literal bracket in class' => ['[]a].txt', '].txt', true];
        yield 'unclosed bracket is literal' => ['a[b', 'a[b', true];
        yield 'braces' => ['*.{jpg,png,gif}', 'x.png', true];
        yield 'nested braces' => ['{a,b{c,d}}.txt', 'bd.txt', true];
        yield 'braces with globs' => ['{logs/*,tmp/**}.log', 'tmp/a/b.log', true];
        yield 'regex chars are literal' => ['a+b(1).txt', 'a+b(1).txt', true];
        yield 'dot is literal' => ['a.txt', 'abtxt', false];
        yield 'escape' => ['\*.txt', '*.txt', true];
        yield 'escaped star is not a wildcard' => ['\*.txt', 'a.txt', false];
        yield 'comma outside braces' => ['a,b', 'a,b', true];
        yield 'closing brace outside braces' => ['a}', 'a}', true];
        yield 'hash is literal' => ['#*', '#tag', true];
        yield 'anchored' => ['b.txt', 'ab.txt', false];
    }

    #[Test]
    #[DataProvider('cases')]
    public function glob_matching(string $glob, string $subject, bool $expected): void
    {
        self::assertSame($expected, preg_match(Glob::toRegex($glob), $subject) === 1);
    }

    #[Test]
    public function ignore_case(): void
    {
        self::assertMatchesRegularExpression(Glob::toRegex('*.JPG', ignoreCase: true), 'a.jpg');
        self::assertDoesNotMatchRegularExpression(Glob::toRegex('*.JPG'), 'a.jpg');
    }
}
