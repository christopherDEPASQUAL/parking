<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\Reservations\CancelReservationRequest;
use App\Application\DTO\Reservations\CreateReservationRequest;
use App\Application\DTO\Reservations\ListParkingReservationsRequest;
use App\Application\UseCase\Reservations\CancelReservation;
use App\Application\UseCase\Reservations\CreateReservation;
use App\Application\UseCase\Reservations\ListParkingReservations;

final class ReservationApiController
{
    public function __construct(
        private readonly CreateReservation $createReservation,
        private readonly CancelReservation $cancelReservation,
        private readonly ListParkingReservations $listParkingReservations
    ) {}

    public function create(): void
    {
        try {
            $data = $this->readJson();
            $request = new CreateReservationRequest(
                $data['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
                $data['user_id'] ?? throw new \InvalidArgumentException('user_id is required'),
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
            $request = new CancelReservationRequest(
                $data['reservation_id'] ?? throw new \InvalidArgumentException('reservation_id is required'),
                $data['actor_user_id'] ?? throw new \InvalidArgumentException('actor_user_id is required'),
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
            $request = new ListParkingReservationsRequest(
                $_GET['parking_id'] ?? throw new \InvalidArgumentException('parking_id is required'),
                $_GET['status'] ?? null,
                isset($_GET['page']) ? (int) $_GET['page'] : 1,
                isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20,
                isset($_GET['from']) ? new \DateTimeImmutable($_GET['from']) : null,
                isset($_GET['to']) ? new \DateTimeImmutable($_GET['to']) : null
            );

            $response = $this->listParkingReservations->execute($request);
            $this->jsonResponse([
                'success' => true,
                'items' => $response->items,
                'page' => $response->page,
                'per_page' => $response->perPage,
                'total' => $response->total,
            ]);
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
