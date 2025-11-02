<?php declare(strict_types=1);

namespace App\Payments;

/**
 * DTO: Instruction to charge (reservationId, amount, customerRef, metadata).
 *
 * Role:
 *  - Boundary type between Presentation and Application layers.
 *  - No framework dependencies.
 */
final class ChargeRequest {}
