<?php declare(strict_types=1);

namespace App\Application\UseCase\Users;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;

final class GetUserProfile
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $userId): User
    {
        $userIdVO = UserId::fromString($userId);
        $user = $this->userRepository->findById($userIdVO);

        if ($user === null) {
            throw new \InvalidArgumentException("User not found");
        }

        return $user;
    }
}