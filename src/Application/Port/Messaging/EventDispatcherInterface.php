<?php declare(strict_types=1);

namespace App\Application\Port\Messaging;

/**
 * Port : dispatcher d'événements de domaine vers les handlers applicatifs.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch un événement unique (synchronement ou asynchronement selon l'implémentation).
     */
    public function dispatch(object $event): void;

    /**
     * Dispatch plusieurs événements.
     *
     * @param iterable<int, object> $events
     */
    public function dispatchAll(iterable $events): void;
}
