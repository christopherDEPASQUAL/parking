<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use PHPUnit\Framework\TestCase;
use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSpot;
use Domain\ValueObject\ParkingId;
use Domain\ValueObject\PricingPlan;
use Domain\ValueObject\GeoLocation;
use Domain\ValueObject\OpeningSchedule;
use Domain\ValueObject\UserId;
use Domain\ValueObject\ParkingSpotId;
use Domain\Exception\ParkingFullException;
use Domain\Exception\SpotAlreadyExistsException;

class ParkingTest extends TestCase
{
    // On déclare les mocks pour éviter de les recréer partout
    private $parkingId;
    private $pricingPlan;
    private $location;
    private $schedule;
    private $userId;

    protected function setUp(): void
    {
        // On crée des doublures (Mocks) pour les dépendances du constructeur
        $this->parkingId = $this->createMock(ParkingId::class);
        $this->pricingPlan = $this->createMock(PricingPlan::class);
        $this->location = $this->createMock(GeoLocation::class);
        $this->schedule = $this->createMock(OpeningSchedule::class);
        $this->userId = $this->createMock(UserId::class);
    }

    // TEST 1: Création valide (Happy Path)
    public function testConstructValidParking(): void
    {
        $parking = new Parking(
            $this->parkingId, 'Parking Test', '1 rue du Test', 10,
            $this->pricingPlan, $this->location, $this->schedule, $this->userId
        );

        $this->assertInstanceOf(Parking::class, $parking);
        $this->assertEquals(10, $parking->getTotalCapacity());
        $this->assertEquals('Parking Test', $parking->getName());
    }

    // TEST 2: Exception si capacité <= 0
    public function testConstructInvalidCapacityThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Parking(
            $this->parkingId, 'Fail', 'Rue', 0, // Capacité 0 !
            $this->pricingPlan, $this->location, $this->schedule, $this->userId
        );
    }

    // TEST 3: Ajout d'une place (Happy Path)
    public function testAddSpotSuccess(): void
    {
        $parking = new Parking(
            $this->parkingId, 'P', 'Rue', 5, 
            $this->pricingPlan, $this->location, $this->schedule, $this->userId
        );

        // On mocke une place de parking avec un ID spécifique
        $spot = $this->createMock(ParkingSpot::class);
        $spotId = $this->createMock(ParkingSpotId::class);
        
        $spotId->method('getValue')->willReturn('spot-123');
        $spot->method('getId')->willReturn($spotId);

        $parking->addSpot($spot);

        // Capacité (5) - 1 place occupée = 4 places physiques libres
        $this->assertEquals(4, $parking->getPhysicalFreeSpotsCount());
    }

    // TEST 4: Erreur si on ajoute deux fois la même place
    public function testAddSpotThrowsExceptionIfDuplicate(): void
    {
        $parking = new Parking(
            $this->parkingId, 'P', 'Rue', 5,
            $this->pricingPlan, $this->location, $this->schedule, $this->userId
        );

        $spot = $this->createMock(ParkingSpot::class);
        $spotId = $this->createMock(ParkingSpotId::class);
        $spotId->method('getValue')->willReturn('spot-A');
        $spot->method('getId')->willReturn($spotId);

        $this->expectException(SpotAlreadyExistsException::class);

        $parking->addSpot($spot);
        $parking->addSpot($spot); // Doublon !
    }

    // TEST 5: Erreur si le parking est plein
    public function testAddSpotThrowsExceptionIfFull(): void
    {
        // Parking avec capacité de 1 seulement
        $parking = new Parking(
            $this->parkingId, 'P', 'Rue', 1,
            $this->pricingPlan, $this->location, $this->schedule, $this->userId
        );

        // Création de 2 places différentes
        $spot1 = $this->createMock(ParkingSpot::class);
        $spot1Id = $this->createMock(ParkingSpotId::class);
        $spot1Id->method('getValue')->willReturn('A');
        $spot1->method('getId')->willReturn($spot1Id);

        $spot2 = $this->createMock(ParkingSpot::class);
        $spot2Id = $this->createMock(ParkingSpotId::class);
        $spot2Id->method('getValue')->willReturn('B');
        $spot2->method('getId')->willReturn($spot2Id);

        $this->expectException(ParkingFullException::class);

        $parking->addSpot($spot1); // OK (1/1)
        $parking->addSpot($spot2); // Boom (2/1)
    }

    // TEST 6: Calcul complexe de disponibilité
    public function testFreeSpotsAt(): void
    {
        // 1. Création d'un parking de 10 places
        $parking = new Parking(
            $this->parkingId, 'P', 'Rue', 10,
            $this->pricingPlan, $this->location, $this->schedule, $this->userId
        );

        $now = new \DateTimeImmutable();

        // 2. Création de faux objets (Stubs) via des classes anonymes
        // pour simuler le comportement de `method_exists`
        
        // Une réservation active maintenant
        $activeRes = new class {
            public function isActiveAt($d) { return true; }
        };

        // Une réservation passée (inactive)
        $inactiveRes = new class {
            public function isActiveAt($d) { return false; }
        };

        // Un abonnement actif qui couvre la date
        $activeAbo = new class {
            public function covers($d) { return true; }
        };

        // Un objet "Cassé" (pas la bonne méthode), pour vérifier la robustesse
        $badObject = new class {}; 

        // 3. Exécution de la méthode
        $freeSpots = $parking->freeSpotsAt(
            $now,
            [$activeRes, $inactiveRes, $badObject], // Réservations (1 active)
            [$activeAbo],                           // Abonnements (1 actif)
            []                                      // Stationnements (0)
        );

        // Calcul attendu :
        // 10 places totales
        // - 1 réservation active
        // - 1 abonnement actif
        // - 0 stationnement
        // = 8 places restantes
        $this->assertEquals(8, $freeSpots);
    }
}