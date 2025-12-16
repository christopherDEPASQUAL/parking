<?php declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Erreur technique (I/O, transport, service externe) remontée à la couche application.
 */
class TechnicalFailureException extends ApplicationException {}
