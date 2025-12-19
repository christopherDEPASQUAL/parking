<?php declare(strict_types=1);

namespace App\Application\UseCase\Reservations;

use App\Application\DTO\Reservations\GetReservationInvoiceRequest;
use App\Application\DTO\Reservations\GetReservationInvoiceResponse;
use App\Application\Exception\ValidationException;
use App\Application\Port\Services\PdfGeneratorInterface;
use App\Domain\Exception\UnauthorizedActionException;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\ValueObject\ReservationId;
use App\Domain\ValueObject\UserId;

final class GetReservationInvoice
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ParkingRepositoryInterface $parkingRepository,
        private readonly PdfGeneratorInterface $pdfGenerator
    ) {}

    public function execute(GetReservationInvoiceRequest $request): GetReservationInvoiceResponse
    {
        $reservationId = ReservationId::fromString($request->reservationId);
        $userId = UserId::fromString($request->userId);

        $reservation = $this->reservationRepository->findById($reservationId);
        if ($reservation === null) {
            throw new ValidationException('Réservation introuvable.');
        }

        if (!$reservation->userId()->equals($userId)) {
            throw new UnauthorizedActionException('Vous n\'êtes pas autorisé à consulter cette facture.');
        }

        $parking = $this->parkingRepository->findById($reservation->parkingId());
        if ($parking === null) {
            throw new ValidationException('Parking introuvable.');
        }

        $dateRange = $reservation->dateRange();
        $price = $reservation->price();

        $html = $this->generateHtml($reservation, $parking, $dateRange, $price);
        $pdfPath = $this->pdfGenerator->generate($html, "invoice_{$reservationId->getValue()}.pdf");

        return new GetReservationInvoiceResponse(
            $reservationId->getValue(),
            $html,
            $pdfPath
        );
    }

    private function generateHtml($reservation, $parking, $dateRange, $price): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Facture - Réservation {$reservation->id()->getValue()}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #333; }
        .info { margin: 20px 0; }
        .total { font-size: 1.2em; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Facture de Réservation</h1>
    <div class="info">
        <p><strong>Réservation ID:</strong> {$reservation->id()->getValue()}</p>
        <p><strong>Parking:</strong> {$parking->getName()}</p>
        <p><strong>Adresse:</strong> {$parking->getAddress()}</p>
        <p><strong>Début:</strong> {$dateRange->getStart()->format('d/m/Y H:i')}</p>
        <p><strong>Fin:</strong> {$dateRange->getEnd()->format('d/m/Y H:i')}</p>
        <p><strong>Statut:</strong> {$reservation->status()->value}</p>
    </div>
    <div class="total">
        <p>Total: " . number_format($price->getAmountInCents() / 100, 2, ',', ' ') . " {$price->getCurrency()}</p>
    </div>
</body>
</html>
HTML;
    }
}

