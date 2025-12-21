<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\Payment;
use App\Domain\Enum\PaymentStatus;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;
use App\Domain\ValueObject\UserId;

final class PaymentRepositoryFile implements PaymentRepositoryInterface
{
    private string $filePath;
    private ReservationRepositoryInterface $reservationRepository;
    private AbonnementRepositoryInterface $abonnementRepository;
    private StationnementRepositoryInterface $stationnementRepository;

    public function __construct(
        ?string $filePath = null,
        ?ReservationRepositoryInterface $reservationRepository = null,
        ?AbonnementRepositoryInterface $abonnementRepository = null,
        ?StationnementRepositoryInterface $stationnementRepository = null
    ) {
        $resolved = $filePath ?? (getenv('JSON_PAYMENT_STORAGE') ?: 'storage/payments.json');
        if (!preg_match('#^([A-Za-z]:\\\\|/)#', $resolved)) {
            $resolved = \dirname(__DIR__, 5) . '/' . ltrim($resolved, '/\\');
        }

        $this->filePath = $resolved;
        $dir = \dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!is_file($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }

        $this->reservationRepository = $reservationRepository ?? new ReservationRepositoryFile();
        $this->abonnementRepository = $abonnementRepository ?? new AbonnementRepositoryFile();
        $this->stationnementRepository = $stationnementRepository ?? new StationnementRepositoryFile();
    }

    public function save(Payment $payment): void
    {
        $records = $this->readAll();
        $records[$payment->id()->getValue()] = $this->toArray($payment);
        $this->persist($records);
    }

    public function findById(PaymentId $id): ?Payment
    {
        $records = $this->readAll();
        if (!isset($records[$id->getValue()])) {
            return null;
        }

        return $this->fromArray($records[$id->getValue()]);
    }

    public function findLatestByReservationId(ReservationId $id): ?Payment
    {
        return $this->findLatestBy('reservation_id', $id->getValue());
    }

    public function findLatestByAbonnementId(AbonnementId $id): ?Payment
    {
        return $this->findLatestBy('abonnement_id', $id->getValue());
    }

    public function findLatestByStationnementId(StationnementId $id): ?Payment
    {
        return $this->findLatestBy('stationnement_id', $id->getValue());
    }

    public function sumApprovedByParkingAndMonth(ParkingId $parkingId, int $year, int $month): int
    {
        $sum = 0;
        foreach ($this->readAll() as $record) {
            if (($record['status'] ?? '') !== PaymentStatus::APPROVED->value) {
                continue;
            }

            $createdAt = new \DateTimeImmutable($record['created_at']);
            if ((int) $createdAt->format('Y') !== $year || (int) $createdAt->format('m') !== $month) {
                continue;
            }

            $recordParkingId = $this->resolveParkingId($record);
            if ($recordParkingId === null || $recordParkingId !== $parkingId->getValue()) {
                continue;
            }

            $sum += (int) ($record['amount_cents'] ?? 0);
        }

        return $sum;
    }

    private function findLatestBy(string $field, string $value): ?Payment
    {
        $latest = null;
        foreach ($this->readAll() as $record) {
            if (($record[$field] ?? null) !== $value) {
                continue;
            }
            $createdAt = new \DateTimeImmutable($record['created_at']);
            if ($latest === null || $createdAt > new \DateTimeImmutable($latest['created_at'])) {
                $latest = $record;
            }
        }

        return $latest ? $this->fromArray($latest) : null;
    }

    private function resolveParkingId(array $record): ?string
    {
        if (!empty($record['reservation_id'])) {
            $reservation = $this->reservationRepository->findById(ReservationId::fromString($record['reservation_id']));
            return $reservation?->parkingId()->getValue();
        }

        if (!empty($record['abonnement_id'])) {
            $abonnement = $this->abonnementRepository->findById(AbonnementId::fromString($record['abonnement_id']));
            return $abonnement?->parkingId()->getValue();
        }

        if (!empty($record['stationnement_id'])) {
            $session = $this->stationnementRepository->findById(StationnementId::fromString($record['stationnement_id']));
            return $session?->getParkingId()->getValue();
        }

        return null;
    }

    private function readAll(): array
    {
        $decoded = json_decode((string) file_get_contents($this->filePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persist(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function toArray(Payment $payment): array
    {
        return [
            'id' => $payment->id()->getValue(),
            'user_id' => $payment->userId()->getValue(),
            'reservation_id' => $payment->reservationId()?->getValue(),
            'abonnement_id' => $payment->abonnementId()?->getValue(),
            'stationnement_id' => $payment->stationnementId()?->getValue(),
            'status' => $payment->status()->value,
            'amount_cents' => $payment->amount()->getAmountInCents(),
            'currency' => $payment->amount()->getCurrency(),
            'provider_reference' => $payment->providerReference(),
            'created_at' => $payment->createdAt()->format(DATE_ATOM),
        ];
    }

    private function fromArray(array $record): Payment
    {
        return new Payment(
            PaymentId::fromString($record['id']),
            UserId::fromString($record['user_id']),
            Money::fromCents((int) $record['amount_cents'], $record['currency'] ?? 'EUR'),
            PaymentStatus::from($record['status']),
            isset($record['reservation_id']) && $record['reservation_id'] !== null
                ? ReservationId::fromString($record['reservation_id'])
                : null,
            isset($record['abonnement_id']) && $record['abonnement_id'] !== null
                ? AbonnementId::fromString($record['abonnement_id'])
                : null,
            isset($record['stationnement_id']) && $record['stationnement_id'] !== null
                ? StationnementId::fromString($record['stationnement_id'])
                : null,
            $record['provider_reference'] ?? null,
            new \DateTimeImmutable($record['created_at'])
        );
    }
}
