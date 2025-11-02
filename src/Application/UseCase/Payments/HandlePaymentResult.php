<?php declare(strict_types=1);

namespace App\Payments;

/**
 * Use case: Handle payment outcome from PaymentPort (approved/refused/pending).
 *
 * Notes:
 *  - No HTTP/SQL here. Coordinates Domain + Ports only.
 *  - Validate input DTOs; return output DTOs.
 */
final class HandlePaymentResult {}
