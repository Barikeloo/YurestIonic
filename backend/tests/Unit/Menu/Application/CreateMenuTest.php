<?php

namespace Tests\Unit\Menu\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Menu\Application\CreateMenu\CreateMenu;
use App\Menu\Application\CreateMenu\CreateMenuCommand;
use App\Menu\Application\CreateMenu\CreateMenuResponse;
use App\Menu\Application\Shared\MenuItemInput;
use App\Menu\Application\Shared\MenuSectionInput;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CreateMenuTest extends TestCase
{
    private MenuRepositoryInterface&MockInterface $menuRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private CreateMenu $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new CreateMenu(
            $this->menuRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_creates_menu_successfully(): void
    {
        $command = new CreateMenuCommand(
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            name: 'Menú del día',
            description: 'Descripción del menú',
            price: 1500,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            active: true,
            sections: [
                new MenuSectionInput(
                    name: 'Primero',
                    position: 0,
                    minChoices: 1,
                    maxChoices: 1,
                    items: [
                        new MenuItemInput(
                            productId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
                            variantId: null,
                            extraPrice: 0,
                            position: 0,
                        ),
                    ],
                ),
            ],
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn ($menu): bool => $menu->name()->value() === 'Menú del día'));

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(CreateMenuResponse::class, $response);
        $this->assertArrayHasKey('id', $response->toArray());
        $this->assertArrayHasKey('name', $response->toArray());
        $this->assertSame('Menú del día', $response->toArray()['name']);
    }

    public function test_creates_menu_with_optional_fields(): void
    {
        $command = new CreateMenuCommand(
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            name: 'Menú básico',
            description: null,
            price: 0,
            validityFrom: '2026-01-01',
            validityTo: '2026-12-31',
            availableDays: 62, // L-V
            availableFromTime: '10:00',
            availableToTime: '22:00',
            active: true,
            sections: [
                new MenuSectionInput(
                    name: 'Único',
                    position: 0,
                    minChoices: 1,
                    maxChoices: 1,
                    items: [
                        new MenuItemInput(
                            productId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
                            variantId: null,
                            extraPrice: 0,
                            position: 0,
                        ),
                    ],
                ),
            ],
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('save')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once();

        $response = ($this->useCase)($command);

        $data = $response->toArray();
        $this->assertSame('Menú básico', $data['name']);
        $this->assertNull($data['description']);
        $this->assertSame(0, $data['price']);
        $this->assertSame('2026-01-01', $data['validity_from']);
        $this->assertSame('2026-12-31', $data['validity_to']);
        $this->assertSame(62, $data['available_days']);
        $this->assertSame('10:00:00', $data['available_from_time']);
        $this->assertSame('22:00:00', $data['available_to_time']);
    }

    public function test_creates_menu_with_multiple_sections_and_items(): void
    {
        $command = new CreateMenuCommand(
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            name: 'Menú completo',
            description: null,
            price: 2500,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            active: true,
            sections: [
                new MenuSectionInput(
                    name: 'Primero',
                    position: 0,
                    minChoices: 1,
                    maxChoices: 1,
                    items: [
                        new MenuItemInput('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11', null, 0, 0),
                        new MenuItemInput('b1eebc99-9c0b-4ef8-bb6d-6bb9bd380b11', null, 0, 1),
                    ],
                ),
                new MenuSectionInput(
                    name: 'Segundo',
                    position: 1,
                    minChoices: 1,
                    maxChoices: 1,
                    items: [
                        new MenuItemInput('b2eebc99-9c0b-4ef8-bb6d-6bb9bd380b11', null, 0, 0),
                    ],
                ),
                new MenuSectionInput(
                    name: 'Postre',
                    position: 2,
                    minChoices: 0,
                    maxChoices: 1,
                    items: [
                        new MenuItemInput('b3eebc99-9c0b-4ef8-bb6d-6bb9bd380b11', null, 200, 0),
                    ],
                ),
            ],
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('save')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft): bool {
                return $draft->metadata['sections_count'] === 3;
            }));

        $response = ($this->useCase)($command);

        $data = $response->toArray();
        $this->assertCount(3, $data['sections']);
        $this->assertCount(2, $data['sections'][0]['items']);
    }
}
