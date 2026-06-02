<?php

namespace Tests\Unit\Menu\Application;

use App\Menu\Application\GetMenu\GetMenu;
use App\Menu\Application\GetMenu\GetMenuCommand;
use App\Menu\Application\GetMenu\GetMenuResponse;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

class GetMenuTest extends TestCase
{
    use MenuEntityTestHelper;

    private MenuRepositoryInterface&MockInterface $menuRepository;
    private GetMenu $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->useCase = new GetMenu($this->menuRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_menu_when_found(): void
    {
        $menuId = Uuid::generate()->value();
        $menu = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Encontrado'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(1500),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$this->createSection('Única')],
        );

        $command = new GetMenuCommand(id: $menuId);

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, true)
            ->andReturn($menu);

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(GetMenuResponse::class, $response);
        $this->assertSame('Encontrado', $response->toArray()['name']);
    }

    public function test_throws_exception_when_menu_not_found(): void
    {
        $menuId = Uuid::generate()->value();

        $command = new GetMenuCommand(id: $menuId);

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, true)
            ->andReturn(null);

        $this->expectException(MenuNotFoundException::class);

        ($this->useCase)($command);
    }
}
