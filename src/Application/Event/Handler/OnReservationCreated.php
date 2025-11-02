<?php declare(strict_types=1);

namespace App\Application\Event\Handler;

/**
 * Application Event Handler
 * React to Domain\Event\ReservationCreated (notify, project, billing).
 *
 * Notes:
 *  - Invoked by EventDispatcherInterface implementation (Infrastructure).
 */
final class OnReservationCreated {}
