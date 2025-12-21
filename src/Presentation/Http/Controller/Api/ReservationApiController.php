<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\UseCase\Reservations\CreateReservation;
use App\Application\UseCase\Reservations\CancelReservation;
use App\Application\UseCase\Reservations\ListUserReservations;
use App\Application\UseCase\Reservations\GetReservationInvoice;
use App\Application\DTO\Reservations\CreateReservationRequest;
use App\Application\DTO\Reservations\CancelReservationRequest;
use App\Application\DTO\Reservations\ListUserReservationsRequest;
use App\Application\DTO\Reservations\GetReservationInvoiceRequest;

final class ReservationApiController
{
    public function __construct(
        private readonly CreateReservation $createReservation,
        private readonly CancelReservation $cancelReservation,
        private readonly ListUserReservations $listUserReservations,
        private readonly GetReservationInvoice $getReservationInvoice
    ) {}

    public function create(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $request = new CreateReservationRequest(
                $data['parkingId'],
                $data['userId'],
                new \DateTimeImmutable($data['startsAt']),
                new \DateTimeImmutable($data['endsAt'])
            );

            $response = $this->createReservation->execute($request);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'reservationId' => $response->reservationId,
                'status' => $response->status,
                'priceCents' => $response->priceCents,
                'currency' => $response->currency
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function cancel(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $request = new CancelReservationRequest(
                $data['reservationId'],
                $data['userId'],
                $data['reason'] ?? null
            );

            $this->cancelReservation->execute($request);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Réservation annulée avec succès.'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function listUserReservations(string $userId): void
    {
        header('Content-Type: application/json');

        try {
            $request = new ListUserReservationsRequest($userId);
            $response = $this->listUserReservations->execute($request);

            http_response_code(200);
            echo json_encode($response->reservations);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function invoice(string $reservationId, string $userId): void
    {
        header('Content-Type: application/json');

        try {
            $request = new GetReservationInvoiceRequest($reservationId, $userId);
            $response = $this->getReservationInvoice->execute($request);

            http_response_code(200);
            echo json_encode([
                'reservationId' => $response->reservationId,
                'html' => $response->html,
                'pdfPath' => $response->pdfPath
            ]);
        } catch (\Exception $e) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
