<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\Condition\Condition;
use Marktaborosi\FlysystemFilter\Condition\SerializableCondition;
use Marktaborosi\FlysystemFilter\Exception\InvalidArgumentException;
use Marktaborosi\FlysystemFilter\Exception\UnserializableConditionException;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Serialization\ConditionSerializer;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SerializationTest extends TestCase
{
    private static function everyBuiltInFilter(): FilterBuilder
    {
        return FilterBuilder::create()
            ->isFile()
            ->not()->isHidden()
            ->groupStart()
                ->extensionEquals(['jpg', 'png'])->or()->pathMatchesGlob('docs/**.md', ignoreCase: true)
                ->or()->filenameMatchesRegex('/^report-\d+$/')
            ->groupEnd()
            ->sizeBetween('1K', '10M')
            ->lastModifiedBetween(1_700_000_000, 1_800_000_000)
            ->depthLte(3)
            ->isPublic()
            ->hasExtension()
            ->visibilityNotEquals('private')
            ->mimeTypeNotContains('video')
            ->hasMetadata('ETag')
            ->metadataIn('StorageClass', ['STANDARD', null]);
    }

    #[Test]
    public function round_trips_through_arrays_and_json(): void
    {
        $filter = self::everyBuiltInFilter();

        $fromArray = FilterBuilder::fromArray($filter->toArray());
        $fromJson = FilterBuilder::fromJson((string) json_encode($filter));

        self::assertSame($filter->toArray(), $fromArray->toArray());
        self::assertSame($filter->toArray(), $fromJson->toArray());
        self::assertSame($filter->describe(), $fromJson->describe());
    }

    #[Test]
    public function restored_filter_behaves_identically(): void
    {
        $filter = self::everyBuiltInFilter();
        $restored = FilterBuilder::fromJson((string) json_encode($filter));
        $item = Items::file('docs/guide/README.MD', 4096, 'public', 1_750_000_000, null, ['ETag' => 'x', 'StorageClass' => 'STANDARD']);

        self::assertTrue($filter->matches($item));
        self::assertTrue($restored->matches($item));
        self::assertFalse($restored->matches(Items::file('.hidden/x.jpg', 4096, 'public', 1_750_000_000, null, ['ETag' => 'x'])));
    }

    #[Test]
    public function array_format_is_stable(): void
    {
        self::assertSame(
            [
                'type' => 'all',
                'conditions' => [
                    ['type' => 'isFile'],
                    ['type' => 'string', 'subject' => 'extension', 'operator' => 'equals', 'values' => ['txt'], 'ignoreCase' => true],
                    ['type' => 'not', 'condition' => ['type' => 'size', 'operator' => '>', 'bytes' => 1024]],
                ],
            ],
            FilterBuilder::create()->isFile()->extensionEquals('txt')->not()->sizeGt('1K')->toArray(),
        );
    }

    #[Test]
    public function closures_cannot_be_serialized(): void
    {
        $this->expectException(UnserializableConditionException::class);
        $this->expectExceptionMessage('"only me"');

        FilterBuilder::create()->isFile()->custom(static fn(): bool => true, 'only me')->toArray();
    }

    /**
     * @return iterable<string, array{array<mixed>, string}>
     */
    public static function invalidDefinitions(): iterable
    {
        yield 'missing type' => [[], 'missing "type"'];
        yield 'unknown type' => [['type' => 'eval'], 'unknown type "eval"'];
        yield 'bad subject' => [['type' => 'string', 'subject' => 'owner', 'operator' => 'equals', 'values' => ['x']], '"subject" must be one of'];
        yield 'bad operator' => [['type' => 'size', 'operator' => '<>', 'bytes' => 1], '"operator" must be one of'];
        yield 'values not strings' => [['type' => 'string', 'subject' => 'path', 'operator' => 'equals', 'values' => [1]], 'list of strings'];
        yield 'negative bytes' => [['type' => 'size', 'operator' => '>', 'bytes' => -1], 'cannot be negative'];
        yield 'bad children' => [['type' => 'all', 'conditions' => ['x']], 'list of objects'];
        yield 'bad regex' => [['type' => 'string', 'subject' => 'path', 'operator' => 'regex', 'values' => ['/(/']], 'Invalid regular expression'];
        yield 'ignoreCase not bool' => [['type' => 'string', 'subject' => 'path', 'operator' => 'equals', 'values' => ['x'], 'ignoreCase' => 'yes'], 'must be a boolean'];
    }

    /**
     * @param array<mixed> $definition
     */
    #[Test]
    #[DataProvider('invalidDefinitions')]
    public function invalid_definitions_are_rejected(array $definition, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        FilterBuilder::fromArray($definition);
    }

    #[Test]
    public function invalid_json_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid filter JSON');

        FilterBuilder::fromJson('{nope');
    }

    #[Test]
    public function regex_can_be_disallowed_for_untrusted_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('regular expressions are not allowed');

        FilterBuilder::fromArray(
            ['type' => 'string', 'subject' => 'path', 'operator' => 'regex', 'values' => ['/x/']],
            new ConditionSerializer(allowRegex: false),
        );
    }

    #[Test]
    public function custom_condition_types_can_be_registered(): void
    {
        $condition = new class (3) implements SerializableCondition {
            public function __construct(private readonly int $min) {}

            public function matches(StorageAttributes $item): bool
            {
                return \strlen($item->path()) >= $this->min;
            }

            public function describe(): string
            {
                return 'path length >= ' . $this->min;
            }

            public function toArray(): array
            {
                return ['type' => 'pathLength', 'min' => $this->min];
            }
        };

        $serializer = (new ConditionSerializer())->register(
            'pathLength',
            static function (array $data) use ($condition): Condition {
                self::assertSame(['type' => 'pathLength', 'min' => 3], $data);

                return $condition;
            },
        );

        $restored = FilterBuilder::fromArray(FilterBuilder::create()->isFile()->where($condition)->toArray(), $serializer);

        self::assertSame('is file AND path length >= 3', $restored->describe());
    }
}
