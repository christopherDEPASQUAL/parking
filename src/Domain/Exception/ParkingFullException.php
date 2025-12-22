<?php
declare(strict_types=1);

namespace App\Domain\Exception;

final class ParkingFullException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Le parking est plein.');
    }
}
