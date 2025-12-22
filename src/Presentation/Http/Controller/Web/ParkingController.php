<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Web;

use App\Application\DTO\Parkings\GetParkingDetailsRequest;
use App\Application\DTO\Parkings\SearchParkingsRequest;
use App\Application\UseCase\Parkings\GetParkingDetails;
use App\Application\UseCase\Parkings\SearchParkings;

final class ParkingController
{
    public function __construct(
        private readonly SearchParkings $searchParkings,
        private readonly GetParkingDetails $getParkingDetails
    ) {}

    public function search(): void
    {
        try {
            $request = SearchParkingsRequest::fromArray($_GET);
            $items = $this->searchParkings->execute($request);
            $this->renderHtml('Parking Search', $items);
        } catch (\Throwable $e) {
            $this->renderHtml('Parking Search Error', ['error' => $e->getMessage()]);
        }
    }

    public function details(): void
    {
        try {
            $request = GetParkingDetailsRequest::fromArray($_GET);
            $details = $this->getParkingDetails->execute($request);
            $this->renderHtml('Parking Details', $details);
        } catch (\Throwable $e) {
            $this->renderHtml('Parking Details Error', ['error' => $e->getMessage()]);
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
