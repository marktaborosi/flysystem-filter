<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\MimeTypeDetector;
use Marktaborosi\FlysystemFilter\Support\MimeTypes;

/**
 * Matches items for which the given {@see Subject} has a value,
 * e.g. files that have an extension or a known visibility.
 */
final readonly class IsKnown implements SerializableCondition
{
    private MimeTypeDetector $mimeTypeDetector;

    public function __construct(public Subject $subject, ?MimeTypeDetector $mimeTypeDetector = null)
    {
        $this->mimeTypeDetector = $mimeTypeDetector ?? MimeTypes::defaultDetector();
    }

    public function matches(StorageAttributes $item): bool
    {
        return $this->subject->resolve($item, $this->mimeTypeDetector) !== null;
    }

    public function describe(): string
    {
        return 'has ' . $this->subject->label();
    }

    public function toArray(): array
    {
        return ['type' => 'isKnown', 'subject' => $this->subject->value];
    }
}
