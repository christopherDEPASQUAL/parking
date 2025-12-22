<?php
declare(strict_types=1);

namespace Tests\Functional;

use App\Domain\Enum\UserRole;

final class OwnerParkingsTest extends FunctionalTestCase
{
    public function testOwnerCanListOwnParkings(): void
    {
        $owner = $this->createUser(UserRole::PROPRIETOR, 'owner@example.com');
        $otherOwner = $this->createUser(UserRole::PROPRIETOR, 'other@example.com');

        $parking = $this->createParking($owner, 'parking-owner');
        $this->createParking($otherOwner, 'parking-other');

        $this->authenticate($owner);

        $response = $this->dispatch('GET', '/owner/parkings');

        self::assertTrue($response['success']);
        self::assertCount(1, $response['items']);
        self::assertSame($parking->getId()->getValue(), $response['items'][0]['id']);
    }
}
