<?php
declare(strict_types=1);

namespace App\Domain\Exception;

final class SpotAlreadyExistsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cette place de parking existe déjà.');
    }
}
