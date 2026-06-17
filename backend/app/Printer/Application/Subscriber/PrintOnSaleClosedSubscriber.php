<?php

declare(strict_types=1);

namespace App\Printer\Application\Subscriber;

use App\Printer\Application\PrintFinalTicket\PrintFinalTicket;
use App\Printer\Application\PrintFinalTicket\PrintFinalTicketCommand;
use App\Sale\Domain\Event\SaleClosed;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;
use Illuminate\Support\Facades\Log;

final class PrintOnSaleClosedSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly PrintFinalTicket $printFinalTicket,
    ) {}

    public function subscribedTo(): array
    {
        return [SaleClosed::class];
    }

    public function handle(DomainEvent $event): void
    {
        /** @var SaleClosed $event */
        try {
            ($this->printFinalTicket)(new PrintFinalTicketCommand($event->orderId()));
        } catch (\Throwable $e) {
            Log::warning('Auto-print failed on sale close', [
                'order_id' => $event->orderId(),
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
