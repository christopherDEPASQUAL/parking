<?php declare(strict_types=1);

namespace App\Application\UseCase\Abonnements;

use App\Application\DTO\Abonnements\CreateAbonnementRequest;
use App\Application\DTO\Payments\ChargeRequest;
use App\Application\Exception\ValidationException;
use App\Application\UseCase\Payments\ProcessPayment;
use App\Domain\Entity\Abonnement;
use App\Domain\Entity\Parking;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\SubscriptionOfferId;
use App\Domain\ValueObject\UserId;
use DateInterval;
use DateTimeImmutable;

final class CreateAbonnement
{
    public function __construct(
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SubscriptionOfferRepositoryInterface $offerRepository,
        private readonly ProcessPayment $processPayment
    ) {}

    /**
     * @return array{abonnement_id:string,status:string,start_date:string,end_date:string}
     */
    public function execute(CreateAbonnementRequest $request): array
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $userId = UserId::fromString($request->userId);
        $offerId = SubscriptionOfferId::fromString($request->offerId);

        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking === null) {
            throw new ValidationException('Parking not found.');
        }

        if ($this->userRepository->findById($userId) === null) {
            throw new ValidationException('User not found.');
        }

        $offer = $this->offerRepository->findById($offerId);
        if ($offer === null) {
            throw new ValidationException('Subscription offer not found.');
        }
        if (!$offer->parkingId()->equals($parkingId)) {
            throw new ValidationException('Offer does not match parking.');
        }
        if ($offer->status() !== 'active') {
            throw new ValidationException('Offer is not available.');
        }

        $this->assertCapacityAvailability(
            $parking,
            $offer->weeklyTimeSlots(),
            $request->startDate,
            $request->endDate
        );

        $status = $request->status ?? 'active';
        $abonnement = new Abonnement(
            AbonnementId::generate(),
            $userId,
            $parkingId,
            $offerId,
            $offer->weeklyTimeSlots(),
            $request->startDate,
            $request->endDate,
            $status
        );

        $this->abonnementRepository->save($abonnement);

        $priceCents = $offer->priceCents();
        $payment = $this->processPayment->execute(new ChargeRequest(
            $userId->getValue(),
            $priceCents,
            'EUR',
            null,
            $abonnement->id()->getValue()
        ));

        if (!$payment->status()->isApproved()) {
            $abonnement->suspend();
            $this->abonnementRepository->save($abonnement);
        }

        return [
            'abonnement_id' => $abonnement->id()->getValue(),
            'offer_id' => $offerId->getValue(),
            'status' => $abonnement->status(),
            'start_date' => $abonnement->startDate()->format(DATE_ATOM),
            'end_date' => $abonnement->endDate()->format(DATE_ATOM),
        ];
    }

    /**
     * @param array<int, array{start_day:int,end_day:int,start_time:string,end_time:string}> $weeklyTimeSlots
     */
    private function assertCapacityAvailability(
        Parking $parking,
        array $weeklyTimeSlots,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): void {
        if ($weeklyTimeSlots === []) {
            return;
        }

        $weekStart = $this->startOfWeek($startDate);
        $lastWeekStart = $this->startOfWeek($endDate);
        $step = new DateInterval('PT15M');

        for ($currentWeek = $weekStart; $currentWeek <= $lastWeekStart; $currentWeek = $currentWeek->modify('+7 days')) {
            foreach ($weeklyTimeSlots as $slot) {
                [$slotStart, $slotEnd] = $this->resolveSlotRange($currentWeek, $slot);
                if ($slotEnd < $startDate || $slotStart > $endDate) {
                    continue;
                }

                for ($cursor = $slotStart; $cursor < $slotEnd; $cursor = $cursor->add($step)) {
                    if ($cursor < $startDate || $cursor > $endDate) {
                        continue;
                    }

                    $context = $this->parkingRepository->getAvailabilityContext($parking->getId(), $cursor);
                    $free = $parking->freeSpotsAt(
                        $cursor,
                        $context['reservations'],
                        $context['abonnements'],
                        $context['stationnements']
                    );
                    if ($free <= 0) {
                        throw new ValidationException('Parking complet sur un creneau d\'abonnement.');
                    }
                }
            }
        }
    }

    private function startOfWeek(DateTimeImmutable $date): DateTimeImmutable
    {
        $day = (int) $date->format('w');
        return $date->setTime(0, 0)->modify('-' . $day . ' days');
    }

    /**
     * @param array{start_day:int,end_day:int,start_time:string,end_time:string} $slot
     * @return array{0:DateTimeImmutable,1:DateTimeImmutable}
     */
    private function resolveSlotRange(DateTimeImmutable $weekStart, array $slot): array
    {
        $startDay = (int) $slot['start_day'];
        $endDay = (int) $slot['end_day'];
        $slotStart = $this->applyDayAndTime($weekStart, $startDay, (string) $slot['start_time']);
        $slotEnd = $this->applyDayAndTime($weekStart, $endDay, (string) $slot['end_time']);

        if ($slotEnd <= $slotStart) {
            $slotEnd = $slotEnd->modify('+7 days');
        }

        return [$slotStart, $slotEnd];
    }

    private function applyDayAndTime(DateTimeImmutable $weekStart, int $dayOffset, string $time): DateTimeImmutable
    {
        [$hour, $minute, $carryDay] = $this->parseTime($time);
        $target = $weekStart->modify('+' . $dayOffset . ' days')->setTime($hour, $minute);
        if ($carryDay) {
            $target = $target->modify('+1 day');
        }

        return $target;
    }

    /**
     * @return array{0:int,1:int,2:bool}
     */
    private function parseTime(string $time): array
    {
        if (!preg_match('/^(2[0-4]|[01]?\\d):([0-5]\\d)$/', $time, $m)) {
            throw new ValidationException('Invalid time format.');
        }

        $hours = (int) $m[1];
        $minutes = (int) $m[2];
        if ($hours === 24 && $minutes !== 0) {
            throw new ValidationException('24:00 must be used with 00 minutes.');
        }

        $carryDay = $hours === 24;
        if ($hours === 24) {
            $hours = 0;
        }

        return [$hours, $minutes, $carryDay];
    }
}
