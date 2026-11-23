<?php

declare(strict_types=1);

namespace Marktaborosi\FlysystemFilter\Exception;

use LogicException;

/**
 * Thrown when a filter expression is malformed, e.g. a dangling `or()`,
 * two operators in a row, an empty group or unbalanced groups.
 *
 * Extends {@see LogicException} for backwards compatibility with v1–v3.
 */
final class InvalidFilterException extends LogicException implements FilterException {}
