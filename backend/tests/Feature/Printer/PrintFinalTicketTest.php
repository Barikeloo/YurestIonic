<?php

declare(strict_types=1);

namespace Tests\Feature\Printer;

use App\Printer\Application\PrintFinalTicket\PrintFinalTicketInterface;
use App\Printer\Domain\Exception\PrinterConnectionException;
use App\Sale\Domain\Exception\OrderFinalTicketNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PrintFinalTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_order_ticket_not_found(): void
    {
        $tenant = $this->createTenantSession();

        $this->mock(PrintFinalTicketInterface::class, function (MockInterface $m) {
            $m->shouldReceive('__invoke')
                ->once()
                ->andThrow(OrderFinalTicketNotFoundException::withOrderId('missing-order'));
        });

        $orderId = '00000000-0000-0000-0000-000000000001';

        $this->withSession($tenant['session'])
            ->postJson("/api/tpv/orders/{$orderId}/print-ticket")
            ->assertStatus(404);
    }

    public function test_returns_200_when_print_succeeds(): void
    {
        $tenant = $this->createTenantSession();

        $this->mock(PrintFinalTicketInterface::class, function (MockInterface $m) {
            $m->shouldReceive('__invoke')->once();
        });

        $orderId = '00000000-0000-0000-0000-000000000002';

        $this->withSession($tenant['session'])
            ->postJson("/api/tpv/orders/{$orderId}/print-ticket")
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Ticket sent to printer.']);
    }

    public function test_returns_422_when_printer_connection_fails(): void
    {
        $tenant = $this->createTenantSession();

        $this->mock(PrintFinalTicketInterface::class, function (MockInterface $m) {
            $m->shouldReceive('__invoke')
                ->once()
                ->andThrow(new PrinterConnectionException('Connection refused to 192.168.1.200:9100'));
        });

        $orderId = '00000000-0000-0000-0000-000000000003';

        $this->withSession($tenant['session'])
            ->postJson("/api/tpv/orders/{$orderId}/print-ticket")
            ->assertStatus(422);
    }
}
