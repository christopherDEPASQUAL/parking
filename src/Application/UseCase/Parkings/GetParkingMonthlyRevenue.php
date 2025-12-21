<?php declare(strict_types=1);

namespace App\Application\UseCase\Parkings;

use App\Application\DTO\Parkings\GetParkingMonthlyRevenueRequest;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\ParkingId;

final class GetParkingMonthlyRevenue
{
    public function __construct(private readonly ParkingRepositoryInterface $parkingRepository) {}

    /**
     * @return array{parking_id:string,amount_cents:int}
     */
    public function execute(GetParkingMonthlyRevenueRequest $request): array
    {
        $parkingId = ParkingId::fromString($request->parkingId);
        $amount = $this->parkingRepository->getMonthlyRevenueCents($parkingId, $request->year, $request->month);

        return [
            'parking_id' => $parkingId->getValue(),
            'amount_cents' => $amount,
        ];
    }
}
