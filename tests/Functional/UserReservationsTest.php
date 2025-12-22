<?php
declare(strict_types=1);

namespace Tests\Functional;

use App\Domain\Enum\UserRole;

final class UserReservationsTest extends FunctionalTestCase
{
    public function testUserCanListReservations(): void
    {
        $owner = $this->createUser(UserRole::PROPRIETOR, 'owner@example.com');
        $user = $this->createUser(UserRole::CLIENT, 'user@example.com');
        $parking = $this->createParking($owner);
        $reservation = $this->createReservation($user, $parking);

        $this->authenticate($user);

        $response = $this->dispatch('GET', '/reservations/me');

        self::assertTrue($response['success']);
        self::assertCount(1, $response['items']);
        self::assertSame($reservation->id()->getValue(), $response['items'][0]['id']);
    }
}
