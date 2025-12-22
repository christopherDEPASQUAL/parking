<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\Payments\ChargeRequest;
use App\Application\Exception\ValidationException;
use App\Application\UseCase\Payments\ProcessPayment;
use App\Domain\Repository\AbonnementRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\SubscriptionOfferRepositoryInterface;
use App\Domain\ValueObject\AbonnementId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\StationnementId;

final class PaymentApiController
{
    public function __construct(
        private readonly ProcessPayment $processPayment,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly AbonnementRepositoryInterface $abonnementRepository,
        private readonly StationnementRepositoryInterface $stationnementRepository,
        private readonly SubscriptionOfferRepositoryInterface $offerRepository
    ) {}

    public function charge(): void
    {
        try {
            $data = $this->readJson();

            $userId = $data['user_id'] ?? null;
            $amountCents = isset($data['amount_cents']) ? (int) $data['amount_cents'] : null;
            $currency = $data['currency'] ?? 'EUR';

            $reservationId = $data['reservation_id'] ?? null;
            $abonnementId = $data['abonnement_id'] ?? null;
            $stationnementId = $data['stationnement_id'] ?? null;

            $targets = array_filter([$reservationId, $abonnementId, $stationnementId], static fn ($value) => $value !== null);
            if (\count($targets) !== 1) {
                throw new ValidationException('Provide exactly one target: reservation_id, abonnement_id, or stationnement_id.');
            }

            $payment = null;

            if ($reservationId !== null) {
                $reservation = $this->reservationRepository->findById(ReservationId::fromString($reservationId));
                if ($reservation === null) {
                    throw new ValidationException('Reservation not found.');
                }

                $userId = $reservation->userId()->getValue();
                $amountCents = $reservation->price()->getAmountInCents();
                $currency = $reservation->price()->getCurrency();

                $payment = $this->processPayment->execute(new ChargeRequest(
                    $userId,
                    $amountCents,
                    $currency,
                    $reservationId
                ));

                if ($payment->status()->isApproved()) {
                    if (!$reservation->status()->isEntryAllowed()) {
                        $reservation->confirm();
                    }
                } elseif (!$reservation->status()->isCompleted() && !$reservation->status()->isCancelled()) {
                    $reservation->markPaymentFailed();
                }

                $this->reservationRepository->save($reservation);
            } elseif ($abonnementId !== null) {
                $abonnement = $this->abonnementRepository->findById(AbonnementId::fromString($abonnementId));
                if ($abonnement === null) {
                    throw new ValidationException('Abonnement not found.');
                }

                $userId = $abonnement->userId()->getValue();
                $offer = $this->offerRepository->findById($abonnement->offerId());
                if ($offer === null) {
                    throw new ValidationException('Subscription offer not found.');
                }
                $amountCents = $offer->priceCents();

                $payment = $this->processPayment->execute(new ChargeRequest(
                    $userId,
                    $amountCents,
                    $currency,
                    null,
                    $abonnementId
                ));

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
            } else {
                $session = $this->stationnementRepository->findById(StationnementId::fromString((string) $stationnementId));
                if ($session === null) {
                    throw new ValidationException('Stationnement not found.');
                }

                $amount = $session->getAmount();
                if ($amount === null) {
                    throw new ValidationException('Stationnement amount not available.');
                }

                $userId = $session->getUserId()->getValue();
                $amountCents = $amount->getAmountInCents();
                $currency = $amount->getCurrency();

                $payment = $this->processPayment->execute(new ChargeRequest(
                    $userId,
                    $amountCents,
                    $currency,
                    null,
                    null,
                    (string) $stationnementId
                ));
            }

            if ($payment === null) {
                throw new ValidationException('Payment could not be created.');
            }

            $this->jsonResponse([
                'success' => true,
                'payment_id' => $payment->id()->getValue(),
                'status' => $payment->status()->value,
                'amount_cents' => $payment->amount()->getAmountInCents(),
                'currency' => $payment->amount()->getCurrency(),
                'provider_reference' => $payment->providerReference(),
                'created_at' => $payment->createdAt()->format(DATE_ATOM),
            ], 201);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    private function readJson(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = $raw ? json_decode($raw, true) : null;

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    private function errorResponse(string $message, int $status): void
    {
        $this->jsonResponse(['success' => false, 'message' => $message], $status);
    }
}
