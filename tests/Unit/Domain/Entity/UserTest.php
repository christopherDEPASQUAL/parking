<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordHash;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRegisterAndVerifyPassword(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        self::assertSame('Ada Lovelace', $user->getFullName());
        self::assertTrue($user->verifyPassword('password123'));
        self::assertFalse($user->verifyPassword('wrong-password'));
    }

    public function testChangeEmailUpdatesTimestamp(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        self::assertNull($user->getUpdatedAt());

        $user->changeEmail(Email::fromString('new@example.com'));

        self::assertSame('new@example.com', $user->getEmail()->getValue());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function testChangeNameRejectsEmpty(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        $this->expectException(\InvalidArgumentException::class);

        $user->changeName('', 'Lovelace');
    }

    public function testChangeRoleUpdates(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        $user->changeRole(UserRole::PROPRIETOR);

        self::assertSame(UserRole::PROPRIETOR, $user->getRole());
    }

    public function testChangePasswordUpdatesTimestamp(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        $user->changePassword(PasswordHash::fromPlainText('newpassword123'));

        self::assertNotNull($user->getUpdatedAt());
    }

    public function testChangeNameNoChangeKeepsTimestamp(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        $user->changeName('Ada', 'Lovelace');

        self::assertNull($user->getUpdatedAt());
    }

    public function testGettersExposeUserData(): void
    {
        $email = Email::fromString('test@example.com');
        $hash = PasswordHash::fromPlainText('password123');
        $user = User::register(
            $email,
            $hash,
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        self::assertNotNull($user->getId());
        self::assertSame($email, $user->getEmail());
        self::assertSame($hash, $user->getPasswordHash());
        self::assertSame('Ada', $user->getFirstName());
        self::assertSame('Lovelace', $user->getLastName());
        self::assertNotNull($user->getCreatedAt());
    }

    public function testChangeEmailWithSameValueDoesNotUpdate(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        $user->changeEmail(Email::fromString('test@example.com'));

        self::assertNull($user->getUpdatedAt());
    }

    public function testChangePasswordWithSameHashDoesNotUpdate(): void
    {
        $user = User::register(
            Email::fromString('test@example.com'),
            PasswordHash::fromPlainText('password123'),
            UserRole::CLIENT,
            'Ada',
            'Lovelace'
        );

        $sameHash = PasswordHash::fromHash($user->getPasswordHash()->getHash());
        $user->changePassword($sameHash);

        self::assertNull($user->getUpdatedAt());
    }
}
