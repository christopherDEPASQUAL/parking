<?php declare(strict_types=1);

namespace App\Payments;

/**
 * DTO: Outcome normalized (ok, status, refusalReason, transactionId).
 *
 * Role:
 *  - Boundary type between Presentation and Application layers.
 *  - No framework dependencies.
 */
final class PaymentResult {}
