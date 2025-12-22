<?php declare(strict_types=1);

namespace App\Presentation\Http\Controller\Api;

use App\Application\UseCase\Users\ChangePassword;
use App\Application\UseCase\Users\GetUserProfile;

final class UserApiController
{
    public function __construct(
        private readonly GetUserProfile $getUserProfile,
        private readonly ChangePassword $changePassword
    ) {}

    public function me(): void
    {
        try {
            $userId = $_SERVER['AUTH_USER_ID'] ?? null;
            if ($userId === null) {
                throw new \InvalidArgumentException('Missing authenticated user.');
            }

            $user = $this->getUserProfile->execute($userId);
            $this->jsonResponse($this->serializeUser($user));
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function changePassword(): void
    {
        try {
            $userId = $_SERVER['AUTH_USER_ID'] ?? null;
            if ($userId === null) {
                throw new \InvalidArgumentException('Missing authenticated user.');
            }

            $data = $this->readJson();
            $current = $data['current_password'] ?? throw new \InvalidArgumentException('current_password is required');
            $new = $data['new_password'] ?? throw new \InvalidArgumentException('new_password is required');

            $user = $this->changePassword->execute($userId, $current, $new);

            $this->jsonResponse([
                'success' => true,
                'user' => $this->serializeUser($user),
            ]);
        } catch (\Throwable $e) {
            $this->errorResponse($e->getMessage(), 400);
        }
    }

    private function readJson(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = $raw ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : [];
    }

    private function serializeUser(\App\Domain\Entity\User $user): array
    {
        return [
            'id' => $user->getId()->getValue(),
            'email' => $user->getEmail()->getValue(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'role' => $user->getRole()->value,
            'created_at' => $user->getCreatedAt()->format(DATE_ATOM),
            'updated_at' => $user->getUpdatedAt()?->format(DATE_ATOM),
        ];
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
