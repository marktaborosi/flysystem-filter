<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Tests\Unit\FilterBuilder\Traits;

use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\Tests\Support\Items;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MetadataFiltersTraitTest extends TestCase
{
    #[Test]
    public function has_metadata(): void
    {
        $filter = (new FilterBuilder())->hasMetadata('ETag');

        self::assertTrue($filter->matches(Items::file('a', extra: ['ETag' => '"abc"'])));
        self::assertTrue($filter->matches(Items::file('a', extra: ['ETag' => null])));
        self::assertFalse($filter->matches(Items::file('a')));
    }

    #[Test]
    public function metadata_equals_is_strict(): void
    {
        $filter = (new FilterBuilder())->metadataEquals('StorageClass', 'GLACIER');

        self::assertTrue($filter->matches(Items::file('a', extra: ['StorageClass' => 'GLACIER'])));
        self::assertFalse($filter->matches(Items::file('a', extra: ['StorageClass' => 'STANDARD'])));
        self::assertFalse($filter->matches(Items::file('a')));
        self::assertFalse((new FilterBuilder())->metadataEquals('n', 1)->matches(Items::file('a', extra: ['n' => '1'])));
    }

    #[Test]
    public function metadata_in(): void
    {
        $filter = (new FilterBuilder())->metadataIn('StorageClass', ['GLACIER', 'DEEP_ARCHIVE']);

        self::assertTrue($filter->matches(Items::dir('a', extra: ['StorageClass' => 'DEEP_ARCHIVE'])));
        self::assertFalse($filter->matches(Items::file('a', extra: ['StorageClass' => 'STANDARD'])));
        self::assertSame('metadata "StorageClass" equals ["GLACIER", "DEEP_ARCHIVE"]', $filter->describe());
    }
}
