<?php

namespace Tests\Unit\Menu\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Menu\Application\SetMenuActive\SetMenuActive;
use App\Menu\Application\SetMenuActive\SetMenuActiveCommand;
use App\Menu\Application\SetMenuActive\SetMenuActiveResponse;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Exception\MenuArchivedException;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

class SetMenuActiveTest extends TestCase
{
    use MenuEntityTestHelper;

    private MenuRepositoryInterface&MockInterface $menuRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private SetMenuActive $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new SetMenuActive(
            $this->menuRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_activates_menu(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Test'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(1000),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: false,
            sections: [$this->createSection('Única')],
        );

        $command = new SetMenuActiveCommand(
            id: $menuId,
            active: true,
            restaurantId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn($menu);

        $this->menuRepository
            ->shouldReceive('save')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(fn (AuditEventDraft $draft): bool => $draft->slug->value() === 'menu.activated'));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(SetMenuActiveResponse::class, $response);
        $this->assertTrue($response->toArray()['active']);
    }

    public function test_deactivates_menu(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Test'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(1000),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$this->createSection('Única')],
        );

        $command = new SetMenuActiveCommand(
            id: $menuId,
            active: false,
            restaurantId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn($menu);

        $this->menuRepository
            ->shouldReceive('save')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(fn (AuditEventDraft $draft): bool => $draft->slug->value() === 'menu.deactivated'));

        $response = ($this->useCase)($command);

        $this->assertFalse($response->toArray()['active']);
    }

    public function test_throws_exception_when_menu_not_found(): void
    {
        $menuId = Uuid::generate()->value();

        $command = new SetMenuActiveCommand(
            id: $menuId,
            active: true,
            restaurantId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
        );

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn(null);

        $this->menuRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(MenuNotFoundException::class);

        ($this->useCase)($command);
    }
}
