<?php declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Port\Messaging\EventDispatcherInterface;

/**
 * Dispatcher basique en mémoire : appelle les handlers enregistrés via container/array.
 */
final class SimpleEventDispatcher implements EventDispatcherInterface
{
    /** @var array<string, list<callable>> */
    private array $listeners = [];

    /**
     * @param array<string, list<callable>> $listeners key = FQCN d'événement, value = liste de callables
     */
    public function __construct(array $listeners = [])
    {
        $this->listeners = $listeners;
    }

    public function dispatch(object $event): void
    {
        $class = $event::class;
        if (!isset($this->listeners[$class])) {
            return;
        }

        foreach ($this->listeners[$class] as $listener) {
            $listener($event);
        }
    }

    public function dispatchAll(iterable $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}
