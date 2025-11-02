<?php declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Thrown when input DTOs fail validation (format/range).
 */
class ValidationException extends \RuntimeException {}
