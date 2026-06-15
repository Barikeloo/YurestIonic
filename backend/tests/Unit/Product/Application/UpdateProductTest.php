<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\UpdateProduct\UpdateProduct;
use App\Product\Application\UpdateProduct\UpdateProductCommand;
use App\Product\Application\UpdateProduct\UpdateProductResponse;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Event\ProductPriceChanged;
use App\Product\Domain\Event\ProductUpdated;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateProductTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function makeProduct(int $price = 100): Product
    {
        $product = Product::dddCreate(
            familyId: Uuid::create('00000000-0000-4000-8000-000000000001'),
            taxId: Uuid::create('00000000-0000-4000-8000-000000000002'),
            imageSrc: ProductImageSrc::create('/images/old.png'),
            name: ProductName::create('Old Name'),
            price: ProductPrice::create($price),
            stock: ProductStock::create(1),
        );
        $product->pullDomainEvents(); // drain ProductCreated (repo uses fromPersistence in production)
        return $product;
    }

    public function test_updates_product_fields(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $product = $this->makeProduct();

        $repository->shouldReceive('findById')->once()->with($product->id()->value())->andReturn($product);
        $repository->shouldReceive('save')->once();
        $eventBus->shouldReceive('publish')->once();

        $useCase = new UpdateProduct($repository, $eventBus);

        $response = $useCase(new UpdateProductCommand(
            id: $product->id()->value(),
            familyId: '00000000-0000-4000-8000-000000000001',
            taxId: '00000000-0000-4000-8000-000000000002',
            imageSrc: '/images/new.png',
            name: 'New Name',
            price: 100,
            stock: 5,
            active: false,
            allergens: [],
        ));

        $this->assertInstanceOf(UpdateProductResponse::class, $response);
        $this->assertSame('New Name', $response->name);
        $this->assertFalse($response->active);
    }

    public function test_publishes_ProductUpdated_and_ProductPriceChanged_when_price_differs(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $product = $this->makeProduct(price: 100);

        $repository->shouldReceive('findById')->once()->andReturn($product);
        $repository->shouldReceive('save')->once();
        $eventBus->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(ProductUpdated::class), Mockery::type(ProductPriceChanged::class));

        $useCase = new UpdateProduct($repository, $eventBus);

        $useCase(new UpdateProductCommand(
            id: $product->id()->value(),
            familyId: '00000000-0000-4000-8000-000000000001',
            taxId: '00000000-0000-4000-8000-000000000002',
            imageSrc: null,
            name: 'New Name',
            price: 200,
            stock: 1,
            active: true,
            allergens: [],
        ));

        $this->addToAssertionCount(1);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $useCase = new UpdateProduct($repository, $eventBus);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new UpdateProductCommand(
            id: 'non-existent-id',
            familyId: '00000000-0000-4000-8000-000000000001',
            taxId: '00000000-0000-4000-8000-000000000002',
            imageSrc: null,
            name: 'Test',
            price: 100,
            stock: 5,
            active: true,
            allergens: [],
        ));
    }
}
