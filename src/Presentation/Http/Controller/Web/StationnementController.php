<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Web;

use App\Application\DTO\Stationnements\ListParkingStationnementsRequest;
use App\Application\DTO\Stationnements\ListUserStationnementsRequest;
use App\Application\UseCase\Stationnements\ListParkingStationnements;
use App\Application\UseCase\Stationnements\ListUserStationnements;

final class StationnementController
{
    public function __construct(
        private readonly ListParkingStationnements $listParkingStationnements,
        private readonly ListUserStationnements $listUserStationnements
    ) {}

    public function listByParking(): void
    {
        try {
            $request = ListParkingStationnementsRequest::fromArray($_GET);
            $items = $this->listParkingStationnements->execute($request);
            $this->renderHtml('Parking Stationnements', ['items' => $items]);
        } catch (\Throwable $e) {
            $this->renderHtml('Stationnements Error', ['error' => $e->getMessage()]);
        }
    }

    public function listByUser(): void
    {
        try {
            $request = ListUserStationnementsRequest::fromArray($_GET);
            $items = $this->listUserStationnements->execute($request);
            $this->renderHtml('User Stationnements', ['items' => $items]);
        } catch (\Throwable $e) {
            $this->renderHtml('Stationnements Error', ['error' => $e->getMessage()]);
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
