<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Condition;

use League\Flysystem\StorageAttributes;

/**
 * Matches items whose extra metadata value under `$key` is strictly equal (`===`) to one of the given values.
 */
final readonly class MetadataEquals implements SerializableCondition
{
    /** @var list<mixed> */
    public array $values;

    /**
     * @param list<mixed> $values
     */
    public function __construct(public string $key, array $values)
    {
        $this->values = array_values($values);
    }

    public function matches(StorageAttributes $item): bool
    {
        $metadata = $item->extraMetadata();

        return \array_key_exists($this->key, $metadata) && \in_array($metadata[$this->key], $this->values, true);
    }

    public function describe(): string
    {
        $values = array_map(self::export(...), $this->values);

        return \sprintf(
            'metadata "%s" equals %s',
            $this->key,
            \count($values) === 1 ? $values[0] : '[' . implode(', ', $values) . ']',
        );
    }

    private static function export(mixed $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? get_debug_type($value) : $json;
    }

    public function toArray(): array
    {
        return ['type' => 'metadataEquals', 'key' => $this->key, 'values' => $this->values];
    }
}
