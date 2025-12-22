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
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\ValueObject\ParkingId;
use App\Domain\ValueObject\UserId;

final class StationnementApiController
{
    public function __construct(
        private readonly EnterParking $enterParking,
        private readonly ExitParking $exitParking,
        private readonly ListParkingStationnements $listParkingStationnements,
        private readonly ListUserStationnements $listUserStationnements,
        private readonly StationnementRepositoryInterface $stationnementRepository
    ) {}

    public function enter(): void
    {
        try {
            $data = $this->readJson();
            if (!isset($data['user_id']) && isset($_SERVER['AUTH_USER_ID'])) {
                $data['user_id'] = $_SERVER['AUTH_USER_ID'];
            }
            if (!isset($data['abonnement_id']) && isset($data['subscription_id'])) {
                $data['abonnement_id'] = $data['subscription_id'];
            }
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
            if (!isset($data['session_id']) && isset($data['parking_id'])) {
                $userId = $_SERVER['AUTH_USER_ID'] ?? null;
                if ($userId === null) {
                    throw new \InvalidArgumentException('user_id is required');
                }
                $session = $this->stationnementRepository->findActiveByUser(
                    UserId::fromString($userId),
                    ParkingId::fromString((string) $data['parking_id'])
                );
                if ($session === null) {
                    throw new \InvalidArgumentException('Active stationing not found.');
                }
                $data['session_id'] = $session->getId()->getValue();
            }
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
            $payload = $_GET;
            if (!isset($payload['parking_id']) && isset($payload['id'])) {
                $payload['parking_id'] = $payload['id'];
            }
            $request = ListParkingStationnementsRequest::fromArray($payload);
            $items = $this->listParkingStationnements->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $this->normalizeStationingItems($items)]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function listByUser(): void
    {
        try {
            $payload = $_GET;
            if (!isset($payload['user_id']) && isset($_SERVER['AUTH_USER_ID'])) {
                $payload['user_id'] = $_SERVER['AUTH_USER_ID'];
            }
            $request = ListUserStationnementsRequest::fromArray($payload);
            $items = $this->listUserStationnements->execute($request);
            $this->jsonResponse(['success' => true, 'items' => $this->normalizeStationingItems($items)]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function enterFromAuth(): void
    {
        $this->enter();
    }

    public function exitFromAuth(): void
    {
        $this->exit();
    }

    public function listMine(): void
    {
        $this->listByUser();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeStationingItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            $normalized[] = [
                'id' => $item['session_id'] ?? $item['id'] ?? null,
                'parking_id' => $item['parking_id'] ?? null,
                'user_id' => $item['user_id'] ?? null,
                'reservation_id' => $item['reservation_id'] ?? null,
                'subscription_id' => $item['abonnement_id'] ?? $item['subscription_id'] ?? null,
                'entered_at' => $item['started_at'] ?? $item['entered_at'] ?? null,
                'exited_at' => $item['ended_at'] ?? $item['exited_at'] ?? null,
                'amount_cents' => $item['amount_cents'] ?? null,
                'currency' => $item['currency'] ?? null,
            ];
        }

        return $normalized;
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
