<?php

namespace Tests\Unit\Menu\Application;

use App\Menu\Application\ArchiveMenu\ArchiveMenu;
use App\Menu\Application\ArchiveMenu\ArchiveMenuCommand;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Event\MenuArchived;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

class ArchiveMenuTest extends TestCase
{
    use MenuEntityTestHelper;

    private MenuRepositoryInterface&MockInterface $menuRepository;
    private EventBusInterface&MockInterface $eventBus;
    private ArchiveMenu $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new ArchiveMenu(
            $this->menuRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_archives_menu_successfully(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::fromPersistence(
            id: $menuId,
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
            name: 'Para archivar',
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

        $command = new ArchiveMenuCommand(
            id: $menuId,
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, true)
            ->andReturn($menu);

        $this->menuRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn ($m): bool => $m->isArchived()));

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(MenuArchived::class));

        ($this->useCase)($command);

        $this->assertTrue($menu->isArchived());
        $this->assertFalse($menu->isActive());
    }

    public function test_archives_already_archived_menu_is_noop(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::fromPersistence(
            id: $menuId,
            taxId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
            name: 'Ya archivado',
            description: null,
            price: 500,
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
        $menu->archive();

        $command = new ArchiveMenuCommand(
            id: $menuId,
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, true)
            ->andReturn($menu);

        $this->menuRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        ($this->useCase)($command);

        $this->assertTrue($menu->isArchived());
    }

    public function test_throws_exception_when_menu_not_found(): void
    {
        $menuId = Uuid::generate()->value();

        $command = new ArchiveMenuCommand(
            id: $menuId,
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, true)
            ->andReturn(null);

        $this->menuRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(MenuNotFoundException::class);

        ($this->useCase)($command);
    }
}
