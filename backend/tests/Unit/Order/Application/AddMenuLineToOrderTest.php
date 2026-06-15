<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Order\Application\AddMenuLineToOrder\AddMenuLineToOrder;
use App\Order\Application\AddMenuLineToOrder\AddMenuLineToOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderMenuLineAdded;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AddMenuLineToOrderTest extends TestCase
{
    private OrderLineRepositoryInterface&MockInterface $orderLineRepository;
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private MenuRepositoryInterface&MockInterface $menuRepository;
    private ProductRepositoryInterface&MockInterface $productRepository;
    private TaxRepositoryInterface&MockInterface $taxRepository;
    private EventBusInterface&MockInterface $eventBus;
    private AddMenuLineToOrder $useCase;

    protected function setUp(): void
    {
        $this->orderLineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->taxRepository = Mockery::mock(TaxRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new AddMenuLineToOrder(
            $this->orderLineRepository,
            $this->orderRepository,
            $this->menuRepository,
            $this->productRepository,
            $this->taxRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testAddsMenuLineAndPublishesEvent(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $menuId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            uuid: $orderId,
            status: 'open',
            tableId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            openedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            closedByUserId: null,
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($order);

        $item = Mockery::mock(\App\Menu\Domain\Entity\MenuItem::class);
        $item->shouldReceive('productId')->andReturn(Uuid::create('c1eebc99-9c0b-4ef8-bb6d-6bb9bd380c99'));
        $item->shouldReceive('extraPrice')->andReturn(\App\Menu\Domain\ValueObject\MenuItemExtraPrice::zero());

        $section = Mockery::mock(\App\Menu\Domain\Entity\MenuSection::class);
        $section->shouldReceive('id')->andReturn(Uuid::create('f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66'));
        $section->shouldReceive('name')->andReturn(\App\Menu\Domain\ValueObject\MenuSectionName::create('Section'));
        $section->shouldReceive('items')->andReturn([$item]);
        $section->shouldReceive('choiceRule')->andReturn(\App\Menu\Domain\ValueObject\MenuSectionChoiceRule::chooseOne());

        $menu = Mockery::mock(Menu::class);
        $menu->shouldReceive('isArchived')->once()->andReturnFalse();
        $menu->shouldReceive('isActive')->once()->andReturnTrue();
        $menu->shouldReceive('taxId')->once()->andReturn(Uuid::create('a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a77'));
        $menu->shouldReceive('sections')->andReturn([$section]);
        $menu->shouldReceive('price')->once()->andReturn(\App\Menu\Domain\ValueObject\MenuPrice::create(2500));
        $menu->shouldReceive('name')->andReturn(\App\Menu\Domain\ValueObject\MenuName::create('Test Menu'));

        $this->menuRepository
            ->shouldReceive('findById')
            ->once()
            ->with($menuId, false)
            ->andReturn($menu);

        $product = Mockery::mock(\App\Product\Domain\Entity\Product::class);
        $product->shouldReceive('isActive')->andReturnTrue();
        $product->shouldReceive('name')->andReturn(\App\Product\Domain\ValueObject\ProductName::create('Test Product'));
        $product->shouldReceive('decreaseStock')->once()->with(1);

        $this->productRepository
            ->shouldReceive('findById')
            ->times(2)
            ->with('c1eebc99-9c0b-4ef8-bb6d-6bb9bd380c99')
            ->andReturn($product);

        $this->productRepository
            ->shouldReceive('save')
            ->once()
            ->with($product);

        $tax = Mockery::mock(\App\Tax\Domain\Entity\Tax::class);
        $tax->shouldReceive('percentage')->once()->andReturn(\App\Tax\Domain\ValueObject\TaxPercentage::create(10));

        $this->taxRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($tax);

        $this->orderLineRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(\App\Order\Domain\Entity\OrderLine::class));

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderMenuLineAdded::class));

        ($this->useCase)(new AddMenuLineToOrderCommand(
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            orderId: $orderId,
            menuId: $menuId,
            userId: 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380b88',
            dinerNumber: null,
            selections: [
                ['section_id' => 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66', 'product_id' => 'c1eebc99-9c0b-4ef8-bb6d-6bb9bd380c99'],
            ],
        ));
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturnNull();

        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderNotFoundException::class);

        ($this->useCase)(new AddMenuLineToOrderCommand(
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            orderId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            menuId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            userId: 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380b88',
            dinerNumber: null,
            selections: [],
        ));
    }
}
