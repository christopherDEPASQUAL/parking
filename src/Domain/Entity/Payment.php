<?php declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\PaymentStatus;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;

final class Payment
{
    private PaymentId $id;
    private UserId $userId;
    private ?ReservationId $reservationId;
    private ?AbonnementId $abonnementId;
    private ?StationnementId $stationnementId;
    private PaymentStatus $status;
    private Money $amount;
    private ?string $providerReference;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        PaymentId $id,
        UserId $userId,
        Money $amount,
        PaymentStatus $status,
        ?ReservationId $reservationId = null,
        ?AbonnementId $abonnementId = null,
        ?StationnementId $stationnementId = null,
        ?string $providerReference = null,
        ?\DateTimeImmutable $createdAt = null
    ) {
        if (($reservationId === null && $abonnementId === null && $stationnementId === null)
            || ($reservationId !== null && $abonnementId !== null)
            || ($reservationId !== null && $stationnementId !== null)
            || ($abonnementId !== null && $stationnementId !== null)) {
            throw new \InvalidArgumentException('Payment must target exactly one domain reference.');
        }

        $this->id = $id;
        $this->userId = $userId;
        $this->amount = $amount;
        $this->status = $status;
        $this->reservationId = $reservationId;
        $this->abonnementId = $abonnementId;
        $this->stationnementId = $stationnementId;
        $this->providerReference = $providerReference;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function reservationId(): ?ReservationId
    {
        return $this->reservationId;
    }

    public function abonnementId(): ?AbonnementId
    {
        return $this->abonnementId;
    }

    public function stationnementId(): ?StationnementId
    {
        return $this->stationnementId;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function providerReference(): ?string
    {
        return $this->providerReference;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
