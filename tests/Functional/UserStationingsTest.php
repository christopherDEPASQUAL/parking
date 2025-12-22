<?php
declare(strict_types=1);

namespace Tests\Functional;

use App\Domain\Enum\UserRole;

final class UserStationingsTest extends FunctionalTestCase
{
    public function testUserCanListStationings(): void
    {
        $owner = $this->createUser(UserRole::PROPRIETOR, 'owner@example.com');
        $user = $this->createUser(UserRole::CLIENT, 'user@example.com');
        $parking = $this->createParking($owner);
        $reservation = $this->createReservation($user, $parking);
        $session = $this->createSession($user, $parking, $reservation);

        $this->authenticate($user);

        $response = $this->dispatch('GET', '/stationings/me');

        self::assertTrue($response['success']);
        self::assertCount(1, $response['items']);
        self::assertSame($session->getId()->getValue(), $response['items'][0]['id']);
    }
}
