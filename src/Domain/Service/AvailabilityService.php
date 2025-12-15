<?php declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use App\Domain\Entity\Abonnement;
use App\Domain\Entity\ParkingSession;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\ParkingId;
use App\Domain\Exception\ReservationNotAvailableException;
use \DateTimeImmutable;

/**
 * Domain service to check slot availability and capacity constraints.
 *
 * Characteristics:
 *  - Stateless; pure domain logic.
 *  - No external I/O.
 */
final class AvailabilityService
{
    /**
     * Calcule le nombre de places disponibles dans un parking à un instant donné.
     *
     * @param Parking $parking Le parking à vérifier
     * @param DateTimeImmutable $at La date/heure à vérifier
     * @param iterable<Reservation> $reservations Liste des réservations du parking
     * @param iterable<Abonnement> $abonnements Liste des abonnements du parking
     * @param iterable<ParkingSession> $stationnements Liste des stationnements du parking
     * @return int Nombre de places disponibles (0 ou plus)
     */
    public function getAvailableSpotsAt(
        Parking $parking,
        DateTimeImmutable $at,
        iterable $reservations = [],
        iterable $abonnements = [],
        iterable $stationnements = []
    ): int {
        // Vérifie si le parking est ouvert à cet instant
        if (!$parking->isOpenAt($at)) {
            return 0;
        }

        $totalCapacity = $parking->getTotalCapacity();
        $usedSpots = 0;

        // Compte les réservations actives à cet instant
        foreach ($reservations as $reservation) {
            if ($this->isReservationActiveAt($reservation, $at)) {
                $usedSpots++;
            }
        }

        // Compte les abonnements actifs à cet instant
        foreach ($abonnements as $abonnement) {
            if ($this->isAbonnementActiveAt($abonnement, $at)) {
                $usedSpots++;
            }
        }

        // Compte les stationnements actifs à cet instant
        foreach ($stationnements as $stationnement) {
            if ($this->isStationnementActiveAt($stationnement, $at)) {
                $usedSpots++;
            }
        }

        return max(0, $totalCapacity - $usedSpots);
    }

    /**
     * Vérifie si une réservation est possible pour une période donnée.
     *
     * @param Parking $parking Le parking concerné
     * @param DateRange $requestedRange La période demandée
     * @param iterable<Reservation> $existingReservations Réservations existantes (hors celle en cours de création)
     * @param iterable<Abonnement> $abonnements Abonnements existants
     * @param iterable<ParkingSession> $stationnements Stationnements existants
     * @return bool True si la réservation est possible
     * @throws ReservationNotAvailableException Si la réservation n'est pas possible
     */
    public function canReserve(
        Parking $parking,
        DateRange $requestedRange,
        iterable $existingReservations = [],
        iterable $abonnements = [],
        iterable $stationnements = []
    ): bool {
        // Vérifie que le parking est ouvert pendant toute la période
        if (!$this->isParkingOpenDuringRange($parking, $requestedRange)) {
            throw new ReservationNotAvailableException(
                'Le parking n\'est pas ouvert pendant la période demandée.'
            );
        }

        // Vérifie la disponibilité à chaque tranche de 15 minutes
        $current = $requestedRange->start();
        $end = $requestedRange->end();

        while ($current < $end) {
            $availableSpots = $this->getAvailableSpotsAt(
                $parking,
                $current,
                $existingReservations,
                $abonnements,
                $stationnements
            );

            if ($availableSpots <= 0) {
                throw new ReservationNotAvailableException(
                    'Aucune place disponible pendant la période demandée.'
                );
            }

            // Avance de 15 minutes
            $current = $current->modify('+15 minutes');
        }

        return true;
    }

    /**
     * Vérifie si le parking est ouvert pendant toute une période.
     */
    private function isParkingOpenDuringRange(Parking $parking, DateRange $range): bool
    {
        $current = $range->start();
        $end = $range->end();

        while ($current < $end) {
            if (!$parking->isOpenAt($current)) {
                return false;
            }

            // Vérifie toutes les 15 minutes
            $current = $current->modify('+15 minutes');
        }

        return true;
    }

    /**
     * Vérifie si une réservation est active à un instant donné.
     */
    private function isReservationActiveAt(Reservation $reservation, DateTimeImmutable $at): bool
    {
        if (!$reservation->isActive()) {
            return false;
        }

        $dateRange = $reservation->dateRange();
        $start = $dateRange->start();
        $end = $dateRange->end();
        
        return $at >= $start && $at <= $end;
    }

    /**
     * Vérifie si un abonnement est actif à un instant donné.
     */
    private function isAbonnementActiveAt(Abonnement $abonnement, DateTimeImmutable $at): bool
    {
        return $abonnement->coversTimeSlot($at);
    }

    /**
     * Vérifie si un stationnement est actif à un instant donné.
     */
    private function isStationnementActiveAt(ParkingSession $stationnement, DateTimeImmutable $at): bool
    {
        $startedAt = $stationnement->getStartedAt();
        $endedAt = $stationnement->getEndedAt();

        // Si le stationnement n'est pas encore commencé
        if ($at < $startedAt) {
            return false;
        }

        // Si le stationnement est terminé
        if ($endedAt !== null && $at >= $endedAt) {
            return false;
        }

        // Le stationnement est actif
        return true;
    }
}

