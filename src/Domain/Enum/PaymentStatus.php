<?php declare(strict_types=1);

namespace App\Domain\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REFUSED = 'refused';

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }
}
