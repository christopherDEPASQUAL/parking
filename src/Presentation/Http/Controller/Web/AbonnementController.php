<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Web;

use App\Application\DTO\Abonnements\ListParkingAbonnementsRequest;
use App\Application\DTO\Abonnements\ListUserAbonnementsRequest;
use App\Application\UseCase\Abonnements\ListParkingAbonnements;
use App\Application\UseCase\Abonnements\ListUserAbonnements;

final class AbonnementController
{
    public function __construct(
        private readonly ListParkingAbonnements $listParkingAbonnements,
        private readonly ListUserAbonnements $listUserAbonnements
    ) {}

    public function listByParking(): void
    {
        try {
            $request = ListParkingAbonnementsRequest::fromArray($_GET);
            $items = $this->listParkingAbonnements->execute($request);
            $this->renderHtml('Parking Abonnements', ['items' => $items]);
        } catch (\Throwable $e) {
            $this->renderHtml('Abonnements Error', ['error' => $e->getMessage()]);
        }
    }

    public function listByUser(): void
    {
        try {
            $request = ListUserAbonnementsRequest::fromArray($_GET);
            $items = $this->listUserAbonnements->execute($request);
            $this->renderHtml('User Abonnements', ['items' => $items]);
        } catch (\Throwable $e) {
            $this->renderHtml('Abonnements Error', ['error' => $e->getMessage()]);
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
