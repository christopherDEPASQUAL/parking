<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Web;

use App\Application\DTO\Reservations\ListParkingReservationsRequest;
use App\Application\UseCase\Reservations\ListParkingReservations;

final class ReservationController
{
    public function __construct(private readonly ListParkingReservations $listParkingReservations) {}

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
            $this->renderHtml('Parking Reservations', [
                'items' => $response->items,
                'page' => $response->page,
                'per_page' => $response->perPage,
                'total' => $response->total,
            ]);
        } catch (\Throwable $e) {
            $this->renderHtml('Reservations Error', ['error' => $e->getMessage()]);
        }
    }

    private function renderHtml(string $title, array $data): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($title) . '</title></head><body>';
        echo '<h1>' . htmlspecialchars($title) . '</h1>';
        echo '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
        echo '</body></html>';
    }
}
