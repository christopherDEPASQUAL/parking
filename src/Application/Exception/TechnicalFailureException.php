<?php declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Maps infra/transport failures to a controlled app error.
 */
class TechnicalFailureException extends \RuntimeException {}
