<?php
namespace Marktaborosi\FlysystemFilter;

use Marktaborosi\FlysystemFilter\Traits\CustomFilterTrait;
use Marktaborosi\FlysystemFilter\Traits\ExtensionFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\FileTypeFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\PathFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\FilenameFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\BasenameFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\SizeFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\DateFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\MimeTypeFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\VisibilityFiltersTrait;
use Marktaborosi\FlysystemFilter\Traits\DepthFiltersTrait;

use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use LogicException;

/**
 * Class FilterBuilder
 *
 * A fluent builder for defining file and directory filters in a chainable, expressive way.
 *
 * This class is part of the Flysystem Filter library, and enables the creation of complex
 * filtering logic using logical operators (`and()`, `or()`), grouping (`group_start()`, `group_end()`),
 * and criteria-based methods for filtering by filename, path, size, date, MIME type, visibility, and depth.
 *
 * FilterBuilder stores a sequence of callable conditions and logical expressions, and uses these
 * to evaluate whether a given Flysystem StorageAttributes item should match.
 *
 * @package Marktaborosi\FlysystemFilter
 *
 **/
class FilterBuilder
{
    use PathFiltersTrait;
    use FilenameFiltersTrait;
    use BasenameFiltersTrait;
    use SizeFiltersTrait;
    use DateFiltersTrait;
    use MimeTypeFiltersTrait;
    use VisibilityFiltersTrait;
    use DepthFiltersTrait;
    use FileTypeFiltersTrait;
    use ExtensionFiltersTrait;
    use CustomFilterTrait;

    /**
     * @var array Stores the current condition group and logical expression tokens.
     */
    private array $currentGroup = [];
    /**
     * @var bool Indicates whether a logical operator is expected next.
     */
    private bool $expectingLogicalOperator = false;
    /**
     * @var int Tracks the depth of nested condition groups.
     */
    private int $groupLevel = 0;
    /**
     * @var FinfoMimeTypeDetector|null Lazy-loaded MIME type detector.
     */
    private ?FinfoMimeTypeDetector $mimeTypeDetector = null;

    /**
     * Adds a logical AND between conditions.
     *
     * @return $this
     */
    public function and(): self
    {
        $this->currentGroup[] = 'AND';
        $this->expectingLogicalOperator = false;
        return $this;
    }

    /**
     * Adds a logical OR between conditions.
     *
     * @return $this
     */
    public function or(): self
    {
        $this->currentGroup[] = 'OR';
        $this->expectingLogicalOperator = false;
        return $this;
    }

    /**
     * Starts a grouped condition block (e.g. parenthesis).
     *
     * @return $this
     */
    public function group_start(): self
    {
        if ($this->expectingLogicalOperator) {
            $this->currentGroup[] = 'AND';
        }

        $this->currentGroup[] = '(';
        $this->expectingLogicalOperator = false;
        $this->groupLevel++;
        return $this;
    }

    /**
     * Ends a grouped condition block.
     *
     * @return $this
     * @throws LogicException if there is no matching group_start
     */
    public function group_end(): self
    {
        if ($this->groupLevel <= 0) {
            throw new LogicException("Mismatched group_end: no corresponding group_start.");
        }

        $this->currentGroup[] = ')';
        $this->expectingLogicalOperator = true;
        $this->groupLevel--;
        return $this;
    }

    /**
     * Evaluates whether a given Flysystem StorageAttributes object matches the built filter.
     *
     * @param StorageAttributes $item
     * @return bool
     * @throws LogicException if groupings are unbalanced
     */
    public function matches(StorageAttributes $item): bool
    {
        if (empty($this->currentGroup)) {
            return true;
        }

        if ($this->groupLevel !== 0) {
            throw new LogicException("Unbalanced groups: missing group_end().");
        }

        $stack = [];
        foreach ($this->currentGroup as $element) {
            if ($element === '(' || $element === ')') {
                $stack[] = $element;
            } elseif ($element === 'AND') {
                $stack[] = '&&';
            } elseif ($element === 'OR') {
                $stack[] = '||';
            } elseif (is_callable($element)) {
                $stack[] = $element($item) ? 'true' : 'false';
            }
        }

        $expression = implode(' ', $stack);
        return eval("return $expression;");
    }

    /**
     * Adds a new condition to the expression stack.
     *
     * @param callable $condition
     * @return void
     */
    private function addCondition(callable $condition): void
    {
        if ($this->expectingLogicalOperator) {
            $this->currentGroup[] = 'AND';
        }

        $this->currentGroup[] = $condition;
        $this->expectingLogicalOperator = true;
    }

    /**
     * Parses human-readable size values (e.g. "10K", "1G") into bytes.
     *
     * @param int|string $size
     * @return int
     * @throws LogicException
     */
    private function parseSize(int|string $size): int
    {
        if (is_int($size)) {
            return $size;
        }

        if (preg_match('/^(\d+)([BKMGT])$/i', $size, $matches)) {
            $value = (int)$matches[1];
            $unit = strtoupper($matches[2]);

            return match ($unit) {
                'B' => $value,
                'K' => $value * 1024,
                'M' => $value * 1024 ** 2,
                'G' => $value * 1024 ** 3,
                'T' => $value * 1024 ** 4,
                default => throw new LogicException("Invalid unit: $unit"),
            };
        }

        throw new LogicException("Invalid size value: $size");
    }

    /**
     * Returns the extension part of the given path.
     *
     * @param string $path
     * @return string|null
     */
    private function getExtension(string $path): ?string
    {
        $info = pathinfo($path);
        return $info['extension'] ?? null;
    }

    /**
     * Detects MIME type based on file extension using league/mime-type-detection.
     *
     * @param string $path
     * @return string|null
     */
    private function getMimeTypeFromPath(string $path): ?string
    {
        if ($this->mimeTypeDetector === null) {
            $this->mimeTypeDetector = new FinfoMimeTypeDetector();
        }

        return $this->mimeTypeDetector->detectMimeTypeFromPath($path);
    }

    /**
     * Counts the number of path segments (i.e., depth).
     *
     * @param string $path
     * @return int
     */
    private function calculateDepth(string $path): int
    {
        return substr_count(trim($path, '/'), '/');
    }

    /**
     * Adds a condition to check if a MIME type contains or does not contain specific substrings.
     *
     * @param array $substrings
     * @param bool $negate Whether to negate the match result.
     * @return $this
     */
    private function filterMimeTypeContains(array $substrings, bool $negate): self
    {
        $this->addCondition(function (StorageAttributes $item) use ($substrings, $negate) {
            $mimeType = $this->getMimeTypeFromPath($item->path());

            if ($mimeType === null) {
                return $negate;
            }

            foreach ($substrings as $substring) {
                if (str_contains($mimeType, $substring)) {
                    return !$negate;
                }
            }

            return $negate;
        });

        return $this;
    }
}