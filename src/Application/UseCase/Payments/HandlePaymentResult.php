<?php declare(strict_types=1);

namespace App\Application\UseCase\Payments;

use App\Domain\Entity\Payment;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;

/**
 * Use case: handle payment outcome if you need a separate flow.
 */
final class HandlePaymentResult
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly StationnementRepositoryInterface $stationnementRepository
    ) {}

    public function execute(Payment $payment): void
    {
        if ($payment->reservationId() !== null) {
            $reservation = $this->reservationRepository->findById($payment->reservationId());
            if ($reservation !== null) {
                if ($payment->status()->isApproved()) {
                    if (!$reservation->status()->isEntryAllowed()) {
                        $reservation->confirm();
                    }
                } elseif (!$reservation->status()->isCompleted() && !$reservation->status()->isCancelled()) {
                    $reservation->markPaymentFailed();
                }

                $this->reservationRepository->save($reservation);
            }

            return;
        }

        if ($payment->abonnementId() !== null) {
            $abonnement = $this->abonnementRepository->findById($payment->abonnementId());
            if ($abonnement !== null) {
                if ($payment->status()->isApproved()) {
                    if ($abonnement->status() === 'suspended') {
                        $abonnement->reactivate();
                    }
                } else {
                    if ($abonnement->status() !== 'suspended') {
                        $abonnement->suspend();
                    }
                }

                $this->abonnementRepository->save($abonnement);
            }

            return;
        }

        if ($payment->stationnementId() !== null) {
            $this->stationnementRepository->findById($payment->stationnementId());
        }
    }
}
