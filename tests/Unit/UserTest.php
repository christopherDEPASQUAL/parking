<?php declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\PasswordHash;
use App\Domain\ValueObject\UserId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    private UserId $userId;
    private Email $email;
    private PasswordHash $passwordHash;
    private UserRole $role;

    protected function setUp(): void
    {
        $this->userId = UserId::fromString('11111111-1111-4111-8111-111111111111');
        $this->email = Email::fromString('test@example.com');
        $this->passwordHash = PasswordHash::fromHash('$2y$10$hashedpassword');
        $this->role = UserRole::CLIENT;
    }

    public function testRegisterCreatesNewUser(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        self::assertInstanceOf(UserId::class, $user->getId());
        self::assertSame($this->email, $user->getEmail());
        self::assertSame($this->passwordHash, $user->getPasswordHash());
        self::assertSame($this->role, $user->getRole());
        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
        self::assertSame('John Doe', $user->getFullName());
        self::assertInstanceOf(DateTimeImmutable::class, $user->getCreatedAt());
        self::assertNull($user->getUpdatedAt());
    }

    public function testFromPersistenceReconstructsUser(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new DateTimeImmutable('2024-01-02 15:00:00');

        $user = User::fromPersistence(
            $this->userId,
            $this->email,
            $this->passwordHash,
            $this->role,
            'Jane',
            'Smith',
            $createdAt,
            $updatedAt
        );

        self::assertSame($this->userId, $user->getId());
        self::assertSame('Jane', $user->getFirstName());
        self::assertSame('Smith', $user->getLastName());
        self::assertSame($createdAt, $user->getCreatedAt());
        self::assertSame($updatedAt, $user->getUpdatedAt());
    }

    public function testRegisterThrowsExceptionIfFirstNameEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('First name cannot be empty');

        User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            '',
            'Doe'
        );
    }

    public function testRegisterThrowsExceptionIfLastNameEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name cannot be empty');

        User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            ''
        );
    }

    public function testRegisterTrimsWhitespaceFromNames(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            '  John  ',
            '  Doe  '
        );

        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        $plainPassword = 'password123';
        $hash = PasswordHash::fromPlainText($plainPassword);

        $user = User::register(
            $this->email,
            $hash,
            $this->role,
            'John',
            'Doe'
        );

        self::assertTrue($user->verifyPassword($plainPassword));
    }

    public function testVerifyPasswordReturnsFalseForIncorrectPassword(): void
    {
        $hash = PasswordHash::fromPlainText('correctpassword');

        $user = User::register(
            $this->email,
            $hash,
            $this->role,
            'John',
            'Doe'
        );

        self::assertFalse($user->verifyPassword('wrongpassword'));
    }

    public function testChangeEmailUpdatesEmailAndTimestamp(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $newEmail = Email::fromString('newemail@example.com');
        $user->changeEmail($newEmail);

        self::assertSame($newEmail, $user->getEmail());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function testChangeEmailDoesNothingIfSameEmail(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $initialUpdatedAt = $user->getUpdatedAt();
        $user->changeEmail($this->email);

        self::assertSame($this->email, $user->getEmail());
        self::assertSame($initialUpdatedAt, $user->getUpdatedAt());
    }

    public function testChangeNameUpdatesNamesAndTimestamp(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $user->changeName('Jane', 'Smith');

        self::assertSame('Jane', $user->getFirstName());
        self::assertSame('Smith', $user->getLastName());
        self::assertSame('Jane Smith', $user->getFullName());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function testChangeNameDoesNothingIfSameNames(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $initialUpdatedAt = $user->getUpdatedAt();
        $user->changeName('John', 'Doe');

        self::assertSame($initialUpdatedAt, $user->getUpdatedAt());
    }

    public function testChangePasswordUpdatesPasswordAndTimestamp(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $newHash = PasswordHash::fromPlainText('newpassword');
        $user->changePassword($newHash);

        self::assertSame($newHash, $user->getPasswordHash());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function testChangePasswordDoesNothingIfSameHash(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $initialUpdatedAt = $user->getUpdatedAt();
        $user->changePassword($this->passwordHash);

        self::assertSame($initialUpdatedAt, $user->getUpdatedAt());
    }

    public function testChangeRoleUpdatesRoleAndTimestamp(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $user->changeRole(UserRole::PROPRIETOR);

        self::assertSame(UserRole::PROPRIETOR, $user->getRole());
        self::assertNotNull($user->getUpdatedAt());
    }

    public function testChangeRoleDoesNothingIfSameRole(): void
    {
        $user = User::register(
            $this->email,
            $this->passwordHash,
            $this->role,
            'John',
            'Doe'
        );

        $initialUpdatedAt = $user->getUpdatedAt();
        $user->changeRole($this->role);

        self::assertSame($initialUpdatedAt, $user->getUpdatedAt());
    }
}

