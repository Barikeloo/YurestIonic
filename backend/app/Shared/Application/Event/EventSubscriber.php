<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

use App\Shared\Domain\Event\DomainEvent;

interface EventSubscriber
{
    public function subscribedTo(): array;

    public function handle(DomainEvent $event): void;
}
