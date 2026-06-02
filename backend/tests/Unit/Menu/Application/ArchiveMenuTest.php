<?php

namespace Tests\Unit\Menu\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Menu\Application\ArchiveMenu\ArchiveMenu;
use App\Menu\Application\ArchiveMenu\ArchiveMenuCommand;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

class ArchiveMenuTest extends TestCase
{
    use MenuEntityTestHelper;

    private MenuRepositoryInterface&MockInterface $menuRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private ArchiveMenu $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new ArchiveMenu(
            $this->menuRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_archives_menu_successfully(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Para archivar'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(1000),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$this->createSection('Única')],
        );

        $command = new ArchiveMenuCommand(
            id: $menuId,
            restaurantId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
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

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

        ($this->useCase)($command);

        $this->assertTrue($menu->isArchived());
        $this->assertFalse($menu->isActive());
    }

    public function test_archives_already_archived_menu_is_noop(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Ya archivado'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(500),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$this->createSection('Única')],
        );
        $menu->archive();

        $command = new ArchiveMenuCommand(
            id: $menuId,
            restaurantId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, true)
            ->andReturn($menu);

        $this->menuRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        ($this->useCase)($command);

        $this->assertTrue($menu->isArchived());
    }

    public function test_throws_exception_when_menu_not_found(): void
    {
        $menuId = Uuid::generate()->value();

        $command = new ArchiveMenuCommand(
            id: $menuId,
            restaurantId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, true)
            ->andReturn(null);

        $this->menuRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(MenuNotFoundException::class);

        ($this->useCase)($command);
    }
}
