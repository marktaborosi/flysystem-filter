<?php

namespace Marktaborosi\FlysystemFilter\Traits;

use Carbon\Carbon;
use League\Flysystem\StorageAttributes;
use Marktaborosi\FlysystemFilter\FilterBuilder;

/**
 * Trait DateFiltersTrait
 *
 * Provides filter conditions for file and directory modification timestamps.
 *
 * These filters operate on the `lastModified()` metadata of Flysystem items,
 * allowing you to constrain results to before, after, or between specific dates.
 *
 * Compatible with both `int` UNIX timestamps and Carbon instances.
 *
 * Designed for use within {@see \Marktaborosi\FlysystemFilter\FilterBuilder}.
 *
 * @package Marktaborosi\FlysystemFilter\Traits
 */
trait DateFiltersTrait
{
    /**
     * Add a condition to match items modified strictly before a given timestamp.
     *
     * @param int|Carbon $timestamp A UNIX timestamp or a Carbon object.
     * @return FilterBuilder|DateFiltersTrait
     */
    public function lastModifiedBefore(int|Carbon $timestamp): self
    {
        $timestampValue = $timestamp instanceof Carbon ? $timestamp->timestamp : $timestamp;
        $this->addCondition(fn(StorageAttributes $item) =>
            $item->lastModified() !== null &&
            $item->lastModified() < $timestampValue
        );
        return $this;
    }

    /**
     * Add a condition to match items modified strictly after a given timestamp.
     *
     * @param int|Carbon $timestamp A UNIX timestamp or a Carbon object.
     * @return FilterBuilder|DateFiltersTrait
     */
    public function lastModifiedAfter(int|Carbon $timestamp): self
    {
        $timestampValue = $timestamp instanceof Carbon ? $timestamp->timestamp : $timestamp;
        $this->addCondition(fn(StorageAttributes $item) =>
            $item->lastModified() !== null &&
            $item->lastModified() > $timestampValue
        );
        return $this;
    }

    /**
     * Add a condition to match items modified between two timestamps, inclusive.
     *
     * @param int|Carbon $start The start timestamp (inclusive).
     * @param int|Carbon $end The end timestamp (inclusive).
     * @return FilterBuilder|DateFiltersTrait
     */
    public function lastModifiedBetween(int|Carbon $start, int|Carbon $end): self
    {
        $startTimestamp = $start instanceof Carbon ? $start->timestamp : $start;
        $endTimestamp = $end instanceof Carbon ? $end->timestamp : $end;
        $this->addCondition(fn(StorageAttributes $item) =>
            $item->lastModified() !== null &&
            $item->lastModified() >= $startTimestamp &&
            $item->lastModified() <= $endTimestamp
        );
        return $this;
    }
}
