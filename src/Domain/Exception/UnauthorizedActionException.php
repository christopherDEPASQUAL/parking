<?php declare(strict_types=1);

namespace App\Domain\Exception;

final class UnauthorizedActionException extends DomainException
{
    public static function becauseRoleCannot(string $role, string $action): self
    {
        return new self(
            sprintf('Role "%s" is not allowed to perform "%s".', $role, $action),
            ['role' => $role, 'action' => $action]
        );
    }

    public static function becauseOwnershipMismatch(string $resourceId): self
    {
        return new self(
            sprintf('Current user is not allowed to operate on resource "%s".', $resourceId),
            ['resource_id' => $resourceId]
        );
    }
}
