<?php declare(strict_types=1);

namespace App\Application\UseCase\Users;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

final class GetUserProfile
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId): User
    {
        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        return $user;
    }
}