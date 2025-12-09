<?php declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Raised when attempting to close an already closed session.
 */
class SessionAlreadyClosedException extends \DomainException {}