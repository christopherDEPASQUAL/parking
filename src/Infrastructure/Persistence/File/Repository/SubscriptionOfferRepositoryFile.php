<?php declare(strict_types=1);

namespace App\Infrastructure\Persistence\File\Repository;

use App\Domain\Entity\SubscriptionOffer;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;

final class SubscriptionOfferRepositoryFile implements SubscriptionOfferRepositoryInterface
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $resolved = $filePath ?? (getenv('JSON_SUBSCRIPTION_OFFER_STORAGE') ?: 'storage/subscription_offers.json');
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
    }

    public function save(SubscriptionOffer $offer): void
    {
        $records = $this->readAll();
        $records[$offer->id()->getValue()] = $this->toArray($offer);
        $this->persist($records);
    }

    public function findById(SubscriptionOfferId $id): ?SubscriptionOffer
    {
        $records = $this->readAll();
        if (!isset($records[$id->getValue()])) {
            return null;
        }

        return $this->fromArray($records[$id->getValue()]);
    }

    public function listByParking(ParkingId $parkingId, ?string $status = null): array
    {
        $result = [];
        foreach ($this->readAll() as $record) {
            if (($record['parking_id'] ?? null) !== $parkingId->getValue()) {
                continue;
            }
            if ($status !== null && ($record['status'] ?? null) !== $status) {
                continue;
            }
            $result[] = $this->fromArray($record);
        }

        return $result;
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

    private function toArray(SubscriptionOffer $offer): array
    {
        return [
            'id' => $offer->id()->getValue(),
            'parking_id' => $offer->parkingId()->getValue(),
            'label' => $offer->label(),
            'type' => $offer->type(),
            'price_cents' => $offer->priceCents(),
            'status' => $offer->status(),
            'weekly_time_slots' => $offer->weeklyTimeSlots(),
        ];
    }

    private function fromArray(array $record): SubscriptionOffer
    {
        return new SubscriptionOffer(
            SubscriptionOfferId::fromString($record['id']),
            ParkingId::fromString($record['parking_id']),
            (string) ($record['label'] ?? ''),
            (string) ($record['type'] ?? 'custom'),
            (int) ($record['price_cents'] ?? 0),
            $this->normalizeSlots($record['weekly_time_slots'] ?? []),
            (string) ($record['status'] ?? 'active')
        );
    }

    /**
     * @param array<int, array<string, mixed>> $slots
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    private function normalizeSlots(array $slots): array
    {
        $normalized = [];
        foreach ($slots as $slot) {
            if (isset($slot['start_day'], $slot['end_day'], $slot['start_time'], $slot['end_time'])) {
                $normalized[] = [
                    'start_day' => (int) $slot['start_day'],
                    'end_day' => (int) $slot['end_day'],
                    'start_time' => (string) $slot['start_time'],
                    'end_time' => (string) $slot['end_time'],
                ];
                continue;
            }

            if (isset($slot['day'], $slot['start'], $slot['end'])) {
                $day = (int) $slot['day'];
                $normalized[] = [
                    'start_day' => $day,
                    'end_day' => $day,
                    'start_time' => (string) $slot['start'],
                    'end_time' => (string) $slot['end'],
                ];
            }
        }

        return $normalized;
    }
}
