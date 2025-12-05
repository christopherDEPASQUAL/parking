<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Raised when a session start/end time is invalid.
 */
class InvalidSessionTimeException extends \DomainException {}