<?php

declare(strict_types=1);

namespace Tests\Unit\Printer\Application;

use App\Printer\Application\PrintFinalTicket\PrintFinalTicketCommand;
use App\Printer\Application\PrintFinalTicket\PrintFinalTicketInterface;
use App\Printer\Application\Subscriber\PrintOnSaleClosedSubscriber;
use App\Sale\Domain\Event\SaleClosed;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PrintOnSaleClosedSubscriberTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private PrintFinalTicketInterface&MockInterface $printFinalTicket;
    private LoggerInterface&MockInterface $logger;
    private PrintOnSaleClosedSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->printFinalTicket = Mockery::mock(PrintFinalTicketInterface::class);
        $this->logger           = Mockery::mock(LoggerInterface::class);
        $this->subscriber       = new PrintOnSaleClosedSubscriber($this->printFinalTicket, $this->logger);
    }

    public function test_subscribed_to_returns_sale_closed(): void
    {
        $this->assertSame([SaleClosed::class], $this->subscriber->subscribedTo());
    }

    public function test_handle_delegates_to_print_final_ticket_with_order_id(): void
    {
        $event = $this->makeSaleClosed('order-uuid-123');

        $this->printFinalTicket
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(
                fn(PrintFinalTicketCommand $cmd) => $cmd->orderId === 'order-uuid-123'
            ));

        $this->subscriber->handle($event);
    }

    public function test_handle_swallows_exception_and_logs_warning(): void
    {
        $event = $this->makeSaleClosed('order-uuid-456');

        $this->printFinalTicket
            ->shouldReceive('__invoke')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        $this->logger
            ->shouldReceive('warning')
            ->once()
            ->with('Auto-print failed on sale close', Mockery::on(
                fn(array $ctx) => $ctx['order_id'] === 'order-uuid-456'
                    && $ctx['error'] === 'Connection refused'
            ));

        // Must not throw — subscriber is fire-and-forget
        $this->subscriber->handle($event);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function makeSaleClosed(string $orderId): SaleClosed
    {
        return new SaleClosed(
            saleId:               'sale-uuid',
            orderId:              $orderId,
            restaurantUuid:       'restaurant-uuid',
            closedByUserIdBefore: null,
            ticketNumberBefore:   null,
            totalCentsBefore:     null,
            closedByUserIdAfter:  'user-uuid',
            ticketNumberAfter:    42,
            totalCentsAfter:      1250,
            totalFormatted:       '12,50 EUR',
            linesCount:           2,
        );
    }
}
