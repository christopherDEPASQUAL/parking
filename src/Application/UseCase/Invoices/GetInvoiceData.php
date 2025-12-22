<?php declare(strict_types=1);

namespace App\Application\UseCase\Invoices;

use App\Application\DTO\Invoices\GetInvoiceRequest;
use App\Application\Exception\ValidationException;
use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;

final class GetInvoiceData
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly StationnementRepositoryInterface $stationnementRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly SubscriptionOfferRepositoryInterface $offerRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(GetInvoiceRequest $request): array
    {
        [$type, $targetId, $payment] = $this->resolveTargetAndPayment($request);

        if (!$payment->status()->isApproved()) {
            throw new ValidationException('Payment not approved.');
        }

        $items = [];
        $context = [];
        $parking = null;
        $user = null;

        if ($type === 'reservation') {
            $reservation = $this->reservationRepository->findById(ReservationId::fromString($targetId));
            if ($reservation === null) {
                throw new ValidationException('Reservation not found.');
            }

            $parking = $this->parkingRepository->findById($reservation->parkingId());
            $user = $this->userRepository->findById($reservation->userId());

            $minutes = (int) ceil($reservation->dateRange()->durationInSeconds() / 60);
            $priceCents = $payment->amount()->getAmountInCents();
            $items[] = [
                'label' => 'Reservation (' . $minutes . ' min)',
                'quantity' => 1,
                'unit_price_cents' => $priceCents,
                'total_cents' => $priceCents,
            ];

            $context = [
                'reservation_id' => $reservation->id()->getValue(),
                'parking_id' => $reservation->parkingId()->getValue(),
                'starts_at' => $reservation->dateRange()->getStart()->format(DATE_ATOM),
                'ends_at' => $reservation->dateRange()->getEnd()->format(DATE_ATOM),
                'duration_minutes' => $minutes,
            ];
        } elseif ($type === 'abonnement') {
            $abonnement = $this->abonnementRepository->findById(AbonnementId::fromString($targetId));
            if ($abonnement === null) {
                throw new ValidationException('Abonnement not found.');
            }

            $parking = $this->parkingRepository->findById($abonnement->parkingId());
            $user = $this->userRepository->findById($abonnement->userId());

            $offer = $this->offerRepository->findById($abonnement->offerId());
            $priceCents = $payment->amount()->getAmountInCents();
            $items[] = [
                'label' => 'Subscription' . ($offer ? (' (' . $offer->label() . ')') : ''),
                'quantity' => 1,
                'unit_price_cents' => $priceCents,
                'total_cents' => $priceCents,
            ];

            $context = [
                'abonnement_id' => $abonnement->id()->getValue(),
                'parking_id' => $abonnement->parkingId()->getValue(),
                'starts_at' => $abonnement->startDate()->format('Y-m-d'),
                'ends_at' => $abonnement->endDate()->format('Y-m-d'),
                'offer_id' => $abonnement->offerId()->getValue(),
                'offer_label' => $offer?->label(),
                'offer_type' => $offer?->type(),
                'offer_price_cents' => $offer?->priceCents(),
                'weekly_time_slots' => $abonnement->weeklyTimeSlots(),
            ];
        } else {
            $session = $this->stationnementRepository->findById(StationnementId::fromString($targetId));
            if ($session === null) {
                throw new ValidationException('Stationnement not found.');
            }

            $parking = $this->parkingRepository->findById($session->getParkingId());
            $user = $this->userRepository->findById($session->getUserId());

            [$items, $context] = $this->buildStationnementLines($session, $payment->amount()->getAmountInCents());
            $context = array_merge([
                'stationnement_id' => $session->getId()->getValue(),
                'parking_id' => $session->getParkingId()->getValue(),
                'reservation_id' => $session->getReservationId()?->getValue(),
                'abonnement_id' => $session->getAbonnementId()?->getValue(),
            ], $context);
        }

        if ($parking === null || $user === null) {
            throw new ValidationException('Parking or user not found.');
        }

        return [
            'invoice_id' => $payment->id()->getValue(),
            'issued_at' => $payment->createdAt()->format(DATE_ATOM),
            'type' => $type,
            'status' => $payment->status()->value,
            'currency' => $payment->amount()->getCurrency(),
            'total_cents' => $payment->amount()->getAmountInCents(),
            'customer' => [
                'id' => $user->getId()->getValue(),
                'name' => $user->getFullName(),
                'email' => (string) $user->getEmail(),
            ],
            'parking' => [
                'id' => $parking->getId()->getValue(),
                'name' => $parking->getName(),
                'address' => $parking->getAddress(),
            ],
            'items' => $items,
            'context' => $context,
        ];
    }

    /**
     * @return array{0:string,1:string,2:\App\Domain\Entity\Payment}
     */
    private function resolveTargetAndPayment(GetInvoiceRequest $request): array
    {
        $targets = array_filter([
            'reservation' => $request->reservationId,
            'abonnement' => $request->abonnementId,
            'stationnement' => $request->stationnementId,
            'payment' => $request->paymentId,
        ], static fn ($value) => $value !== null);

        if (\count($targets) !== 1) {
            throw new ValidationException('Provide exactly one target for invoice lookup.');
        }

        $type = array_key_first($targets);
        $targetId = (string) array_values($targets)[0];

        if ($type === 'payment') {
            $payment = $this->paymentRepository->findById(PaymentId::fromString($targetId));
            if ($payment === null) {
                throw new ValidationException('Payment not found.');
            }

            if ($payment->reservationId() !== null) {
                return ['reservation', $payment->reservationId()->getValue(), $payment];
            }
            if ($payment->abonnementId() !== null) {
                return ['abonnement', $payment->abonnementId()->getValue(), $payment];
            }
            if ($payment->stationnementId() !== null) {
                return ['stationnement', $payment->stationnementId()->getValue(), $payment];
            }

            throw new ValidationException('Payment has no target.');
        }

        $payment = match ($type) {
            'reservation' => $this->paymentRepository->findLatestByReservationId(ReservationId::fromString($targetId)),
            'abonnement' => $this->paymentRepository->findLatestByAbonnementId(AbonnementId::fromString($targetId)),
            'stationnement' => $this->paymentRepository->findLatestByStationnementId(StationnementId::fromString($targetId)),
            default => null,
        };

        if ($payment === null) {
            throw new ValidationException('Payment not found for target.');
        }

        return [$type, $targetId, $payment];
    }

    /**
     * @return array{0:array<int, array<string, mixed>>,1:array<string, mixed>}
     */
    private function buildStationnementLines(ParkingSession $session, int $paymentTotalCents): array
    {
        $parking = $this->parkingRepository->findById($session->getParkingId());
        if ($parking === null) {
            throw new ValidationException('Parking not found.');
        }

        $pricing = $parking->getPricingPlan();
        $endedAt = $session->getEndedAt() ?? new \DateTimeImmutable();
        $actualMinutes = $session->durationMinutes($endedAt);

        $reservedMinutes = 0;
        $overstayMinutes = 0;
        $baseCents = 0;
        $penaltyCents = 0;
        $source = 'reservation';

        if ($session->getReservationId() !== null) {
            $reservation = $this->reservationRepository->findById($session->getReservationId());
            if ($reservation !== null) {
                $reservedMinutes = (int) ceil($reservation->dateRange()->durationInSeconds() / 60);
                $billableMinutes = max($reservedMinutes, $actualMinutes);
                $overstayMinutes = max(0, $actualMinutes - $reservedMinutes);
                $baseCents = $pricing->computePriceCents($billableMinutes);
                $penaltyCents = $actualMinutes > $reservedMinutes ? $pricing->getOverstayPenaltyCents() : 0;
            }
        } else {
            $source = 'abonnement';
            $abonnement = $this->abonnementRepository->findById($session->getAbonnementId());
            if ($abonnement !== null) {
                $reservedMinutes = $this->resolveSlotRemainingMinutes($abonnement->weeklyTimeSlots(), $session);
                $overstayMinutes = max(0, $actualMinutes - $reservedMinutes);
                $baseCents = $pricing->computePriceCents($overstayMinutes);
                $penaltyCents = $overstayMinutes > 0 ? $pricing->getOverstayPenaltyCents() : 0;
            }
        }

        $items = [];
        if ($baseCents > 0) {
            $items[] = [
                'label' => 'Overstay charge (' . $source . ')',
                'quantity' => 1,
                'unit_price_cents' => $baseCents,
                'total_cents' => $baseCents,
            ];
        }
        if ($penaltyCents > 0) {
            $items[] = [
                'label' => 'Overstay penalty',
                'quantity' => 1,
                'unit_price_cents' => $penaltyCents,
                'total_cents' => $penaltyCents,
            ];
        }

        $computedTotal = $baseCents + $penaltyCents;
        $adjustment = $paymentTotalCents - $computedTotal;
        if ($adjustment !== 0) {
            $items[] = [
                'label' => 'Adjustment',
                'quantity' => 1,
                'unit_price_cents' => $adjustment,
                'total_cents' => $adjustment,
            ];
        }

        if ($items === []) {
            $items[] = [
                'label' => 'Stationnement',
                'quantity' => 1,
                'unit_price_cents' => $paymentTotalCents,
                'total_cents' => $paymentTotalCents,
            ];
        }

        $context = [
            'started_at' => $session->getStartedAt()->format(DATE_ATOM),
            'ended_at' => $endedAt->format(DATE_ATOM),
            'duration_minutes' => $actualMinutes,
            'reserved_minutes' => $reservedMinutes,
            'overstay_minutes' => $overstayMinutes,
            'source' => $source,
        ];

        return [$items, $context];
    }

    /**
     * @param array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}> $slots
     */
    private function resolveSlotRemainingMinutes(array $slots, ParkingSession $session): int
    {
        $startedAt = $session->getStartedAt();
        $startMinutes = $this->toWeekMinutes((int) $startedAt->format('w'), $startedAt->format('H:i'));

        foreach ($slots as $slot) {
            $slotStart = $this->toWeekMinutes((int) $slot['start_day'], (string) $slot['start_time']);
            $slotEnd = $this->toWeekMinutes((int) $slot['end_day'], (string) $slot['end_time']);

            if ($slotStart <= $slotEnd) {
                if ($startMinutes < $slotStart || $startMinutes > $slotEnd) {
                    continue;
                }
                return max(0, $slotEnd - $startMinutes);
            }

            if (!($startMinutes >= $slotStart || $startMinutes <= $slotEnd)) {
                continue;
            }

            $adjustedEnd = $slotEnd + (7 * 1440);
            $adjustedStart = $startMinutes;
            if ($startMinutes <= $slotEnd) {
                $adjustedStart += 7 * 1440;
            }

            return max(0, $adjustedEnd - $adjustedStart);
        }

        return 0;
    }

    private function toWeekMinutes(int $dayOfWeek, string $time): int
    {
        return ($dayOfWeek * 1440) + $this->timeToMinutes($time);
    }

    private function timeToMinutes(string $time): int
    {
        if (!preg_match('/^(2[0-4]|[01]?\\d):([0-5]\\d)$/', $time, $m)) {
            throw new ValidationException('Invalid time format.');
        }

        $hours = (int) $m[1];
        $minutes = (int) $m[2];
        if ($hours === 24 && $minutes !== 0) {
            throw new ValidationException('24:00 must be used with 00 minutes.');
        }

        return $hours * 60 + $minutes;
    }
}
