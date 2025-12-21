<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\DTO\Stationnements\EnterParkingRequest;
use App\Application\DTO\Stationnements\ExitParkingRequest;
use App\Application\DTO\Stationnements\ListParkingStationnementsRequest;
use App\Application\DTO\Stationnements\ListUserStationnementsRequest;
use App\Application\UseCase\Stationnements\EnterParking;
use App\Application\UseCase\Stationnements\ExitParking;
use App\Application\UseCase\Stationnements\ListParkingStationnements;
use App\Application\UseCase\Stationnements\ListUserStationnements;

final class StationnementApiController
{
    public function __construct(
        private readonly EnterParking $enterParking,
        private readonly ExitParking $exitParking,
        private readonly ListParkingStationnements $listParkingStationnements,
        private readonly ListUserStationnements $listUserStationnements
    ) {}

    public function enter(): void
    {
        try {
            $data = $this->readJson();
            $request = EnterParkingRequest::fromArray($data);
            $result = $this->enterParking->execute($request);
            $this->jsonResponse(['success' => true] + $result, 201);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function exit(): void
    {
        try {
            $data = $this->readJson();
            $request = ExitParkingRequest::fromArray($data);
            $result = $this->exitParking->execute($request);
            $this->jsonResponse(['success' => true] + $result);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listByParking(): void
    {
        try {
            $request = ListParkingStationnementsRequest::fromArray($_GET);
            $items = $this->listParkingStationnements->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $items]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listByUser(): void
    {
        try {
            $request = ListUserStationnementsRequest::fromArray($_GET);
            $items = $this->listUserStationnements->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $items]);
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
