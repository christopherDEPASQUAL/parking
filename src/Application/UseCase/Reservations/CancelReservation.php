<?php declare(strict_types=1);

namespace App\Application\UseCase\Reservations;

use App\Application\DTO\Reservations\CancelReservationRequest;
use App\Application\DTO\Reservations\CancelReservationResponse;
use App\Application\Exception\ValidationException;
use App\Domain\Enum\UserRole;
use App\Domain\Event\ReservationCancelled;
use App\Application\Port\Messaging\EventDispatcherInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;

/**
 * Cas d'usage : annuler une réservation (avec contrôle d'autorisation).
 */
final class CancelReservation
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function execute(CancelReservationRequest $request): CancelReservationResponse
    {
        $reservationId = ReservationId::fromString($request->reservationId);
        $reservation = $this->reservationRepository->findById($reservationId);

        if ($reservation === null) {
            throw new ValidationException('Réservation introuvable.');
        }

        $actorId = UserId::fromString($request->actorUserId);
        $actor = $this->userRepository->findById($actorId);
        if ($actor === null) {
            throw new ValidationException('Utilisateur acteur introuvable.');
        }

        $this->assertActorCanCancel($actor->getRole(), $actorId, $reservation->userId(), $reservation->parkingId());

        $reservation->cancel($request->reason);
        $this->reservationRepository->save($reservation);

        $this->eventDispatcher->dispatch(
            new ReservationCancelled(
                $reservation->id(),
                $reservation->userId(),
                $reservation->parkingId(),
                $reservation->cancelledAt(),
                $reservation->cancellationReason()
            )
        );

        return new CancelReservationResponse(
            $reservation->id()->getValue(),
            $reservation->status()->value,
            $reservation->cancelledAt(),
            $reservation->cancellationReason()
        );
    }

    private function assertActorCanCancel(UserRole $actorRole, UserId $actorId, UserId $reservationUserId, \App\Domain\ValueObject\ParkingId $parkingId): void
    {
        // Admin : toujours autorisé
        if ($actorRole === UserRole::ADMIN) {
            return;
        }

        // Propriétaire du parking : autorisé
        $parking = $this->parkingRepository->findById($parkingId);
        if ($parking !== null && $parking->getUserId()->equals($actorId)) {
            return;
        }

        // Propriétaire de la réservation (utilisateur) : autorisé
        if ($reservationUserId->equals($actorId)) {
            return;
        }

        throw new ValidationException('Utilisateur non autorisé à annuler cette réservation.');
    }
}
