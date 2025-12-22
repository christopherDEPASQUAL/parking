<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\Reservations\CancelReservationRequest;
use App\Application\DTO\Reservations\CreateReservationRequest;
use App\Application\DTO\Reservations\ListParkingReservationsRequest;
use App\Application\UseCase\Reservations\CancelReservation;
use App\Application\UseCase\Reservations\CreateReservation;
use App\Application\UseCase\Reservations\ListParkingReservations;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;

final class ReservationApiController
{
    public function __construct(
        private readonly CreateReservation $createReservation,
        private readonly CancelReservation $cancelReservation,
        private readonly ListParkingReservations $listParkingReservations,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ParkingRepositoryInterface $parkingRepository
    ) {}

    public function create(): void
    {
        try {
            $data = $this->readJson();
            $userId = $data['user_id'] ?? ($_SERVER['AUTH_USER_ID'] ?? null);
            if ($userId === null) {
                throw new \InvalidArgumentException('user_id is required');
            }
            $request = new CreateReservationRequest(
                $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
                $userId,
                new \DateTimeImmutable($data['starts_at'] ?? throw new \InvalidArgumentException('starts_at is required')),
                new \DateTimeImmutable($data['ends_at'] ?? throw new \InvalidArgumentException('ends_at is required'))
            );

            $response = $this->createReservation->execute($request);

            $this->jsonResponse([
                'success' => true,
                'reservation_id' => $response->reservationId,
                'status' => $response->status,
                'price_cents' => $response->priceCents,
                'currency' => $response->currency,
                'starts_at' => $response->startsAt->format(DATE_ATOM),
                'ends_at' => $response->endsAt->format(DATE_ATOM),
            ], 201);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function cancel(): void
    {
        try {
            $data = $this->readJson();
            $reservationId = $data['reservation_id'] ?? ($_GET['id'] ?? null);
            if ($reservationId === null) {
                throw new \InvalidArgumentException('reservation_id is required');
            }
            $actorUserId = $data['actor_user_id'] ?? ($_SERVER['AUTH_USER_ID'] ?? null);
            if ($actorUserId === null) {
                throw new \InvalidArgumentException('actor_user_id is required');
            }
            $request = new CancelReservationRequest(
                $reservationId,
                $actorUserId,
                $data['reason'] ?? null
            );

            $response = $this->cancelReservation->execute($request);
            $this->jsonResponse([
                'success' => true,
                'reservation_id' => $response->reservationId,
                'status' => $response->status,
                'cancelled_at' => $response->cancelledAt?->format(DATE_ATOM),
                'reason' => $response->reason,
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listByParking(): void
    {
        try {
            $parkingId = $_GET['parking_id'] ?? ($_GET['id'] ?? null);
            if ($parkingId === null) {
                throw new \InvalidArgumentException('parking_id is required');
            }
            $this->assertOwnerAccess($parkingId);
            $request = new ListParkingReservationsRequest(
                $parkingId,
                $_GET['status'] ?? null,
                isset($_GET['from']) ? new \DateTimeImmutable($_GET['from']) : null,
                isset($_GET['to']) ? new \DateTimeImmutable($_GET['to']) : null,
                isset($_GET['page']) ? (int) $_GET['page'] : 1,
                isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20
            );

            $response = $this->listParkingReservations->execute($request);
            $this->jsonResponse([
                'success' => true,
                'items' => $this->mapReservationItems($response->items, $_GET['parking_id'] ?? ($_GET['id'] ?? null)),
                'page' => $response->page,
                'per_page' => $response->perPage,
                'total' => $response->total,
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listMine(): void
    {
        try {
            $userId = $_SERVER['AUTH_USER_ID'] ?? null;
            if ($userId === null) {
                throw new \InvalidArgumentException('Missing authenticated user.');
            }

            $items = $this->reservationRepository->listByUser(UserId::fromString($userId));
            $this->jsonResponse(['success' => true, 'items' => $this->mapReservationItems($items)]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function details(): void
    {
        try {
            $reservationId = $_GET['id'] ?? null;
            if ($reservationId === null) {
                throw new \InvalidArgumentException('reservation_id is required');
            }

            $reservation = $this->reservationRepository->findById(ReservationId::fromString($reservationId));
            if ($reservation === null) {
                throw new \InvalidArgumentException('Reservation not found.');
            }

            $authUserId = $_SERVER['AUTH_USER_ID'] ?? null;
            $role = $_SERVER['AUTH_USER_ROLE'] ?? null;
            if ($authUserId !== null && $reservation->userId()->getValue() !== $authUserId && $role !== 'admin') {
                throw new \InvalidArgumentException('Not authorized to view this reservation.');
            }

            $this->jsonResponse($this->serializeReservation($reservation));
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function cancelFromAuth(): void
    {
        $this->cancel();
    }

    public function createFromAuth(): void
    {
        $this->create();
    }

    /**
     * @param array<int, mixed> $items
     * @param string|null $parkingId
     * @return array<int, array<string, mixed>>
     */
    private function mapReservationItems(array $items, ?string $parkingId = null): array
    {
        $normalized = [];
        foreach ($items as $reservation) {
            if ($reservation instanceof \App\Domain\Entity\Reservation) {
                $normalized[] = $this->serializeReservation($reservation);
                continue;
            }

            if (is_array($reservation)) {
                $payload = [
                    'id' => $reservation['reservationId'] ?? $reservation['id'] ?? null,
                    'user_id' => $reservation['userId'] ?? $reservation['user_id'] ?? null,
                    'parking_id' => $reservation['parkingId'] ?? $reservation['parking_id'] ?? $parkingId,
                    'starts_at' => $reservation['startsAt'] ?? $reservation['starts_at'] ?? null,
                    'ends_at' => $reservation['endsAt'] ?? $reservation['ends_at'] ?? null,
                    'status' => isset($reservation['status']) ? strtolower((string) $reservation['status']) : null,
                ];

                if (isset($reservation['priceCents']) || isset($reservation['price_cents'])) {
                    $payload['price_cents'] = $reservation['priceCents'] ?? $reservation['price_cents'];
                }
                if (isset($reservation['currency'])) {
                    $payload['currency'] = $reservation['currency'];
                }

                $normalized[] = $payload;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeReservation(\App\Domain\Entity\Reservation $reservation): array
    {
        return [
            'id' => $reservation->id()->getValue(),
            'user_id' => $reservation->userId()->getValue(),
            'parking_id' => $reservation->parkingId()->getValue(),
            'starts_at' => $reservation->dateRange()->getStart()->format(DATE_ATOM),
            'ends_at' => $reservation->dateRange()->getEnd()->format(DATE_ATOM),
            'status' => strtolower($reservation->status()->value),
            'price_cents' => $reservation->price()->getAmountInCents(),
            'currency' => $reservation->price()->getCurrency(),
            'created_at' => $reservation->createdAt()->format(DATE_ATOM),
            'cancelled_at' => $reservation->cancelledAt()?->format(DATE_ATOM),
            'cancellation_reason' => $reservation->cancellationReason(),
        ];
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

    private function assertOwnerAccess(string $parkingId): void
    {
        $authUserId = $_SERVER['AUTH_USER_ID'] ?? null;
        $role = $_SERVER['AUTH_USER_ROLE'] ?? null;
        if ($authUserId === null) {
            throw new \InvalidArgumentException('Missing authenticated user.');
        }
        if ($role === 'admin') {
            return;
        }
        if ($role !== 'proprietor') {
            throw new \InvalidArgumentException('Owner access required.');
        }
        $parking = $this->parkingRepository->findById(ParkingId::fromString($parkingId));
        if ($parking === null) {
            throw new \InvalidArgumentException('Parking not found.');
        }
        if ($parking->getUserId()->getValue() !== $authUserId) {
            throw new \InvalidArgumentException('Not authorized to access this parking.');
        }
    }
}
