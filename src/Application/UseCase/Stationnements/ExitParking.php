<?php declare(strict_types=1);

namespace App\Application\UseCase\Stationnements;

use App\Application\DTO\Payments\ChargeRequest;
use App\Application\DTO\Stationnements\ExitParkingRequest;
use App\Application\Exception\ValidationException;
use App\Application\UseCase\Payments\ProcessPayment;
use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\StationnementId;

final class ExitParking
{
    public function __construct(
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly StationnementRepositoryInterface $stationnementRepository,
        private readonly ProcessPayment $processPayment
    ) {}

    /**
     * @return array{session_id:string,ended_at:string,amount_cents:int,currency:string}
     */
    public function execute(ExitParkingRequest $request): array
    {
        $sessionId = StationnementId::fromString($request->sessionId);
        $session = $this->stationnementRepository->findById($sessionId);

        if ($session === null) {
            throw new ValidationException('Session not found.');
        }
        if (!$session->isActive()) {
            throw new ValidationException('Session already closed.');
        }

        $endedAt = $request->at ?? new \DateTimeImmutable();
        $parking = $this->parkingRepository->findById($session->getParkingId());
        if ($parking === null) {
            throw new ValidationException('Parking not found.');
        }

        $reservedMinutes = 0;
        $isAbonnement = false;
        $reservation = null;
        if ($session->getReservationId() !== null) {
            $reservation = $this->reservationRepository->findById($session->getReservationId());
            if ($reservation !== null) {
                $reservedMinutes = (int) ceil($reservation->dateRange()->durationInSeconds() / 60);
            }
        } elseif ($session->getAbonnementId() !== null) {
            $isAbonnement = true;
            $abonnement = $this->abonnementRepository->findById($session->getAbonnementId());
            if ($abonnement !== null) {
                $reservedMinutes = $this->resolveSlotRemainingMinutes($abonnement->weeklyTimeSlots(), $session);
            }
        }

        $actualMinutes = $session->durationMinutes($endedAt);
        if ($isAbonnement) {
            $overstayMinutes = max(0, $actualMinutes - $reservedMinutes);
            if ($overstayMinutes > 0) {
                $priceCents = $parking->getPricingPlan()->computePriceCents($overstayMinutes)
                    + $parking->getPricingPlan()->getOverstayPenaltyCents();
            } else {
                $priceCents = 0;
            }
        } else {
            $billableMinutes = max($reservedMinutes, $actualMinutes);
            $priceCents = $parking->getPricingPlan()->computeOverstayPriceCents($reservedMinutes, $billableMinutes);
        }
        $amount = Money::fromCents($priceCents);

        $session->close($endedAt, $amount);
        $this->stationnementRepository->save($session);

        $this->processPayment->execute(new ChargeRequest(
            $session->getUserId()->getValue(),
            $amount->getAmountInCents(),
            $amount->getCurrency(),
            null,
            null,
            $session->getId()->getValue()
        ));

        if ($reservation !== null && $reservation->isActive()) {
            $reservation->markAsCompleted();
            $this->reservationRepository->save($reservation);
        }

        return [
            'session_id' => $session->getId()->getValue(),
            'ended_at' => $session->getEndedAt()?->format(DATE_ATOM) ?? $endedAt->format(DATE_ATOM),
            'amount_cents' => $amount->getAmountInCents(),
            'currency' => $amount->getCurrency(),
        ];
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
                $remaining = $slotEnd - $startMinutes;
                return max(0, $remaining);
            }

            if (!($startMinutes >= $slotStart || $startMinutes <= $slotEnd)) {
                continue;
            }

            $adjustedEnd = $slotEnd + (7 * 1440);
            $adjustedStart = $startMinutes;
            if ($startMinutes <= $slotEnd) {
                $adjustedStart += 7 * 1440;
            }

            $remaining = $adjustedEnd - $adjustedStart;
            return max(0, $remaining);
        }

        return 0;
    }

    private function toWeekMinutes(int $dayOfWeek, string $time): int
    {
        $minutes = $this->timeToMinutes($time);
        return ($dayOfWeek * 1440) + $minutes;
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
