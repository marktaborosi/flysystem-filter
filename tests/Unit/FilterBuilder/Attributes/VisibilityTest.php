<?php

namespace FilterBuilder\Attributes;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class VisibilityTest
 *
 * Unit tests for visibility-based filtering using the FilterBuilder.
 * Tests cover both public and private filters, including positive and negative matching.
 */
class VisibilityTest extends TestCase
{
    /**
     * Test that isPublic() and isPrivate() filters correctly match their respective visibility values.
     *
     * @throws Exception
     */
    public function test_visibility_filters()
    {
        $publicFilter = (new FilterBuilder())->isPublic();
        $privateFilter = (new FilterBuilder())->isPrivate();

        $publicItem = $this->createMock(StorageAttributes::class);
        $publicItem->method('visibility')->willReturn('public');

        $privateItem = $this->createMock(StorageAttributes::class);
        $privateItem->method('visibility')->willReturn('private');

        $this->assertTrue($publicFilter->matches($publicItem), 'Expected public item to match isPublic()');
        $this->assertTrue($privateFilter->matches($privateItem), 'Expected private item to match isPrivate()');
    }

    /**
     * Test that isPublic() does not match an item with private visibility.
     *
     * @throws Exception
     */
    public function test_is_public_does_not_match_private_item()
    {
        $filter = new FilterBuilder();
        $filter->isPublic();

        $item = $this->createMock(StorageAttributes::class);
        $item->method('visibility')->willReturn('private');

        $this->assertFalse($filter->matches($item), 'Expected private item to not match isPublic()');
    }

    /**
     * Test that isPrivate() does not match an item with public visibility.
     *
     * @throws Exception
     */
    public function test_is_private_does_not_match_public_item()
    {
        $filter = new FilterBuilder();
        $filter->isPrivate();

        $item = $this->createMock(StorageAttributes::class);
        $item->method('visibility')->willReturn('public');

        $this->assertFalse($filter->matches($item), 'Expected public item to not match isPrivate()');
    }
}
