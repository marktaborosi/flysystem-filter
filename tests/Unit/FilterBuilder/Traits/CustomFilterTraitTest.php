<?php

namespace FilterBuilder\Traits;

use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CustomFilterTraitTest extends TestCase
{

    /**
     * @throws Exception
     */
    public function test_custom_callback_filters_items()
    {
        $filter = new FilterBuilder();
        $filter->custom(function (StorageAttributes $item) {
            return str_ends_with($item->path(), '.important');
        });

        $item = $this->createMock(StorageAttributes::class);
        $item->method('path')->willReturn('user/files/document.important');

        $this->assertTrue($filter->matches($item));
    }

}