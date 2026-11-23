<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Exception;

/**
 * Thrown when a filter method receives an unusable argument,
 * e.g. an invalid regular expression, size string or date.
 *
 * Extends the SPL {@see \InvalidArgumentException} (which itself is a {@see \LogicException}).
 */
final class InvalidArgumentException extends \InvalidArgumentException implements FilterException {}
