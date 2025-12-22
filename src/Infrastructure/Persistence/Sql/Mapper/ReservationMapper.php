<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\Sql\Mapper;

use App\Domain\Entity\Reservation;
use App\Domain\Enum\ReservationStatus;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;

final class ReservationMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Reservation $reservation): array
    {
        return [
            'id' => $reservation->id()->getValue(),
            'user_id' => $reservation->userId()->getValue(),
            'parking_id' => $reservation->parkingId()->getValue(),
            'starts_at' => $reservation->dateRange()->getStart()->format('Y-m-d H:i:s'),
            'ends_at' => $reservation->dateRange()->getEnd()->format('Y-m-d H:i:s'),
            'status' => strtolower($reservation->status()->value),
            'price' => number_format($reservation->price()->toFloat(), 2, '.', ''),
            'currency' => $reservation->price()->getCurrency(),
            'created_at' => $reservation->createdAt()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    public function fromArray(array $row): Reservation
    {
        $range = DateRange::fromDateTimes(
            new \DateTimeImmutable((string) $row['starts_at']),
            new \DateTimeImmutable((string) $row['ends_at'])
        );

        $status = ReservationStatus::from(strtoupper((string) $row['status']));
        $currency = isset($row['currency']) ? (string) $row['currency'] : 'EUR';
        $price = isset($row['price']) ? Money::fromFloat((float) $row['price'], $currency) : Money::fromCents(0, $currency);

        return new Reservation(
            ReservationId::fromString((string) $row['id']),
            UserId::fromString((string) $row['user_id']),
            ParkingId::fromString((string) $row['parking_id']),
            $range,
            $price,
            $status,
            isset($row['created_at']) ? new \DateTimeImmutable((string) $row['created_at']) : null
        );
    }
}
