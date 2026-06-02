<?php

namespace Tests\Unit\Menu\Application;

use App\Menu\Application\ListMenus\ListMenus;
use App\Menu\Application\ListMenus\ListMenusCommand;
use App\Menu\Application\ListMenus\ListMenusResponse;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Menu\Domain\Entity\MenuEntityTestHelper;

class ListMenusTest extends TestCase
{
    use MenuEntityTestHelper;

    private MenuRepositoryInterface&MockInterface $menuRepository;
    private ListMenus $useCase;

    protected function setUp(): void
    {
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->useCase = new ListMenus($this->menuRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_all_menus_without_filters(): void
    {
        $menu1 = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Menú 1'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(1000),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$this->createSection('Sección')],
        );
        $menu2 = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Menú 2'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(2000),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: false,
            sections: [$this->createSection('Sección')],
        );

        $command = new ListMenusCommand();

        $this->menuRepository
            ->shouldReceive('findAllByCurrentRestaurant')
            ->once()
            ->with([])
            ->andReturn([$menu1, $menu2]);

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(ListMenusResponse::class, $response);
        $this->assertCount(2, $response->toArray());
        $this->assertSame('Menú 1', $response->toArray()[0]['name']);
        $this->assertSame('Menú 2', $response->toArray()[1]['name']);
    }

    public function test_filters_by_active(): void
    {
        $activeMenu = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: \App\Menu\Domain\ValueObject\MenuName::create('Activo'),
            description: \App\Menu\Domain\ValueObject\MenuDescription::empty(),
            price: \App\Menu\Domain\ValueObject\MenuPrice::create(1000),
            validity: \App\Menu\Domain\ValueObject\MenuValidity::always(),
            availability: \App\Menu\Domain\ValueObject\MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$this->createSection('Sección')],
        );

        $command = new ListMenusCommand(active: true);

        $this->menuRepository
            ->shouldReceive('findAllByCurrentRestaurant')
            ->once()
            ->with(['active' => true])
            ->andReturn([$activeMenu]);

        $response = ($this->useCase)($command);

        $this->assertCount(1, $response->toArray());
        $this->assertTrue($response->toArray()[0]['active']);
    }

    public function test_filters_by_archived(): void
    {
        $command = new ListMenusCommand(archived: false);

        $this->menuRepository
            ->shouldReceive('findAllByCurrentRestaurant')
            ->once()
            ->with(['archived' => false])
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->toArray());
    }

    public function test_filters_by_search(): void
    {
        $command = new ListMenusCommand(search: 'menú');

        $this->menuRepository
            ->shouldReceive('findAllByCurrentRestaurant')
            ->once()
            ->with(['search' => 'menú'])
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->toArray());
    }

    public function test_filters_with_empty_search_omits_filter(): void
    {
        $command = new ListMenusCommand(search: '');

        $this->menuRepository
            ->shouldReceive('findAllByCurrentRestaurant')
            ->once()
            ->with([])
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->toArray());
    }
}
