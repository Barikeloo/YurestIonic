<?php

namespace Tests\Unit\Menu\Application;

use App\Menu\Application\Shared\MenuItemInput;
use App\Menu\Application\Shared\MenuSectionInput;
use App\Menu\Application\UpdateMenu\UpdateMenu;
use App\Menu\Application\UpdateMenu\UpdateMenuCommand;
use App\Menu\Application\UpdateMenu\UpdateMenuResponse;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Event\MenuUpdated;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

class UpdateMenuTest extends TestCase
{
    use MenuEntityTestHelper;

    private MenuRepositoryInterface&MockInterface $menuRepository;
    private EventBusInterface&MockInterface $eventBus;
    private UpdateMenu $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new UpdateMenu(
            $this->menuRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_updates_menu_successfully(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::fromPersistence(
            id: $menuId,
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
            name: 'Original',
            description: 'Original desc',
            price: 1000,
            active: true,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            sections: [$this->createSection('Original section')],
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            archivedAt: null,
        );

        $command = new UpdateMenuCommand(
            id: $menuId,
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
            name: 'Actualizado',
            description: 'Nueva descripción',
            price: 2000,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            active: false,
            sections: [
                new MenuSectionInput(
                    name: 'Nueva sección',
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
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn($menu);

        $this->menuRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn ($m): bool => $m->name()->value() === 'Actualizado' && !$m->isActive()));

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(MenuUpdated::class));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(UpdateMenuResponse::class, $response);
        $data = $response->toArray();
        $this->assertSame('Actualizado', $data['name']);
        $this->assertFalse($data['active']);
    }

    public function test_throws_exception_when_menu_not_found(): void
    {
        $menuId = Uuid::generate()->value();

        $command = new UpdateMenuCommand(
            id: $menuId,
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            name: 'Nuevo',
            description: null,
            price: 1000,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            active: true,
            sections: [
                new MenuSectionInput(
                    name: 'Sección',
                    position: 0,
                    minChoices: 1,
                    maxChoices: 1,
                    items: [
                        new MenuItemInput('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11', null, 0, 0),
                    ],
                ),
            ],
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn(null);

        $this->menuRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(MenuNotFoundException::class);

        ($this->useCase)($command);
    }
}
