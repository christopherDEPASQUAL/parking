<?php declare(strict_types=1);

namespace App\Application\UseCase\SubscriptionOffers;

use App\Application\DTO\SubscriptionOffers\CreateSubscriptionOfferRequest;
use App\Application\Exception\ValidationException;
use App\Domain\Entity\SubscriptionOffer;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;

final class CreateSubscriptionOffer
{
    public function __construct(
        private readonly SubscriptionOfferRepositoryInterface $offerRepository,
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    /**
     * @return array{offer_id:string,status:string}
     */
    public function execute(CreateSubscriptionOfferRequest $request): array
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        if ($this->parkingRepository->findById($parkingId) === null) {
            throw new ValidationException('Parking not found.');
        }

        $slots = $this->normalizeSlots($request->weeklyTimeSlots, $request->type);
        if ($slots === []) {
            throw new ValidationException('weekly_time_slots is required for this offer.');
        }

        $offer = new SubscriptionOffer(
            SubscriptionOfferId::generate(),
            $parkingId,
            $request->label,
            $request->type,
            $request->priceCents,
            $slots,
            $request->status ?? 'active'
        );

        $this->offerRepository->save($offer);

        return [
            'offer_id' => $offer->id()->getValue(),
            'status' => $offer->status(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $slots
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    private function normalizeSlots(array $slots, string $type): array
    {
        if ($slots === []) {
            $defaults = $this->defaultSlotsForType($type);
            if ($defaults !== []) {
                return $defaults;
            }
        }

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

            $day = isset($slot['day']) ? (int) $slot['day'] : null;
            $start = $slot['start'] ?? null;
            $end = $slot['end'] ?? null;

            if ($day === null || $start === null || $end === null) {
                throw new ValidationException('Invalid weekly time slot definition.');
            }

            $startDay = $day;
            $endDay = $day;
            $startTime = (string) $start;
            $endTime = (string) $end;

            if ($startTime > $endTime) {
                $endDay = $day === 6 ? 0 : $day + 1;
            }

            $normalized[] = [
                'start_day' => $startDay,
                'end_day' => $endDay,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}>
     */
    private function defaultSlotsForType(string $type): array
    {
        $key = strtolower($type);

        if ($key === 'weekend') {
            return [[
                'start_day' => 5,
                'end_day' => 1,
                'start_time' => '18:00',
                'end_time' => '10:00',
            ]];
        }

        if ($key === 'evening') {
            $slots = [];
            for ($day = 0; $day <= 6; $day++) {
                $slots[] = [
                    'start_day' => $day,
                    'end_day' => $day === 6 ? 0 : $day + 1,
                    'start_time' => '18:00',
                    'end_time' => '08:00',
                ];
            }
            return $slots;
        }

        if ($key === 'full') {
            $slots = [];
            for ($day = 0; $day <= 6; $day++) {
                $slots[] = [
                    'start_day' => $day,
                    'end_day' => $day,
                    'start_time' => '00:00',
                    'end_time' => '24:00',
                ];
            }
            return $slots;
        }

        return [];
    }
}
