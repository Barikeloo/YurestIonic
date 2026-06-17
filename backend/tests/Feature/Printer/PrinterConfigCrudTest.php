<?php

declare(strict_types=1);

namespace Tests\Feature\Printer;

use App\Printer\Domain\Exception\PrinterConnectionException;
use App\Printer\Domain\Interfaces\PrinterServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PrinterConfigCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_list_update_delete_printer_config(): void
    {
        $tenant = $this->createTenantSession('admin');

        // Create
        $create = $this->withSession($tenant['session'])
            ->postJson('/api/admin/printers', [
                'name'        => 'Sala',
                'ip'          => '192.168.1.200',
                'port'        => 9100,
                'paper_width' => 80,
                'enabled'     => true,
                'is_default'  => true,
            ]);

        $create->assertStatus(201)
            ->assertJsonFragment([
                'name'        => 'Sala',
                'ip'          => '192.168.1.200',
                'port'        => 9100,
                'paper_width' => 80,
                'enabled'     => true,
                'is_default'  => true,
            ]);

        $uuid = $create->json('uuid');
        $this->assertNotNull($uuid);

        // List
        $this->withSession($tenant['session'])
            ->getJson('/api/admin/printers')
            ->assertStatus(200)
            ->assertJsonFragment(['uuid' => $uuid]);

        // Update
        $this->withSession($tenant['session'])
            ->putJson("/api/admin/printers/{$uuid}", [
                'name'        => 'Cocina',
                'ip'          => '192.168.1.201',
                'port'        => 9100,
                'paper_width' => 58,
                'enabled'     => false,
                'is_default'  => false,
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'name'        => 'Cocina',
                'ip'          => '192.168.1.201',
                'paper_width' => 58,
                'enabled'     => false,
            ]);

        // Delete
        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/printers/{$uuid}")
            ->assertStatus(204);

        // Confirm gone from list
        $this->withSession($tenant['session'])
            ->getJson('/api/admin/printers')
            ->assertStatus(200)
            ->assertJsonMissing(['uuid' => $uuid]);
    }

    public function test_create_printer_with_zone_uuid(): void
    {
        $tenant = $this->createTenantSession('admin');

        // Create a zone first so the UUID is a valid UUID format (not FK constrained here)
        $zoneUuid = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/printers', [
                'name'      => 'Terraza',
                'ip'        => '192.168.1.202',
                'zone_uuid' => $zoneUuid,
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['zone_uuid' => $zoneUuid]);
    }

    public function test_create_printer_requires_name_and_ip(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/printers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'ip']);
    }

    public function test_test_endpoint_returns_200_when_printer_reachable(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->mock(PrinterServiceInterface::class, function (MockInterface $m) {
            $m->shouldReceive('send')->once();
        });

        $create = $this->withSession($tenant['session'])
            ->postJson('/api/admin/printers', [
                'name' => 'Test',
                'ip'   => '192.168.1.200',
            ]);
        $uuid = $create->json('uuid');

        $this->withSession($tenant['session'])
            ->postJson("/api/admin/printers/{$uuid}/test")
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Test page sent.']);
    }

    public function test_test_endpoint_returns_422_when_printer_unreachable(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->mock(PrinterServiceInterface::class, function (MockInterface $m) {
            $m->shouldReceive('send')->once()
                ->andThrow(new PrinterConnectionException('Connection refused'));
        });

        $create = $this->withSession($tenant['session'])
            ->postJson('/api/admin/printers', [
                'name' => 'OfflinePrinter',
                'ip'   => '192.168.99.99',
            ]);
        $uuid = $create->json('uuid');

        $this->withSession($tenant['session'])
            ->postJson("/api/admin/printers/{$uuid}/test")
            ->assertStatus(422);
    }

    public function test_update_nonexistent_printer_returns_404(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->putJson('/api/admin/printers/00000000-0000-0000-0000-000000000000', [
                'name' => 'Ghost',
                'ip'   => '192.168.1.1',
            ])
            ->assertStatus(404);
    }
}
