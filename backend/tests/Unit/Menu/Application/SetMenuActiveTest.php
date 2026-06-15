<?php

namespace Tests\Unit\Menu\Application;

use App\Menu\Application\SetMenuActive\SetMenuActive;
use App\Menu\Application\SetMenuActive\SetMenuActiveCommand;
use App\Menu\Application\SetMenuActive\SetMenuActiveResponse;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Event\MenuActivated;
use App\Menu\Domain\Event\MenuDeactivated;
use App\Menu\Domain\Exception\MenuArchivedException;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

class SetMenuActiveTest extends TestCase
{
    use MenuEntityTestHelper;

    private MenuRepositoryInterface&MockInterface $menuRepository;
    private EventBusInterface&MockInterface $eventBus;
    private SetMenuActive $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new SetMenuActive(
            $this->menuRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_activates_menu(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::fromPersistence(
            id: $menuId,
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
            name: 'Test',
            description: null,
            price: 1000,
            active: false,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            sections: [$this->createSection('Única')],
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            archivedAt: null,
        );

        $command = new SetMenuActiveCommand(
            id: $menuId,
            active: true,
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn($menu);

        $this->menuRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(MenuActivated::class));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(SetMenuActiveResponse::class, $response);
        $this->assertTrue($response->toArray()['active']);
    }

    public function test_deactivates_menu(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::fromPersistence(
            id: $menuId,
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
            name: 'Test',
            description: null,
            price: 1000,
            active: true,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            sections: [$this->createSection('Única')],
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            archivedAt: null,
        );

        $command = new SetMenuActiveCommand(
            id: $menuId,
            active: false,
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn($menu);

        $this->menuRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(MenuDeactivated::class));

        $response = ($this->useCase)($command);

        $this->assertFalse($response->toArray()['active']);
    }

    public function test_throws_exception_when_menu_not_found(): void
    {
        $menuId = Uuid::generate()->value();

        $command = new SetMenuActiveCommand(
            id: $menuId,
            active: true,
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
