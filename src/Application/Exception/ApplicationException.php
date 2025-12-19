<?php declare(strict_types=1);

namespace App\Application\Exception;

/**
 * Exception de base pour la couche application.
 * Permet de distinguer les erreurs métier/validation de celles d'infrastructure.
 */
class ApplicationException extends \RuntimeException {}
