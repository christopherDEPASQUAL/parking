<?php
declare(strict_types=1);

namespace Tests\Functional;

use App\Domain\Enum\UserRole;

final class OwnerReservationsTest extends FunctionalTestCase
{
    public function testOwnerCanListReservationsForParking(): void
    {
        $owner = $this->createUser(UserRole::PROPRIETOR, 'owner@example.com');
        $user = $this->createUser(UserRole::CLIENT, 'user@example.com');
        $parking = $this->createParking($owner, 'parking-owner');
        $reservation = $this->createReservation($user, $parking);

        $this->authenticate($owner);

        $response = $this->dispatch('GET', '/owner/parkings/' . $parking->getId()->getValue() . '/reservations');

        self::assertTrue($response['success']);
        self::assertSame($reservation->id()->getValue(), $response['items'][0]['id']);
    }
}
