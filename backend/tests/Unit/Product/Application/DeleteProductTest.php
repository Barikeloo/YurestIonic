<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\DeleteProduct\DeleteProduct;
use App\Product\Application\DeleteProduct\DeleteProductCommand;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Event\ProductDeleted;
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

class DeleteProductTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function makeProduct(): Product
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Coca Cola'),
            price: ProductPrice::create(250),
            stock: ProductStock::create(10),
        );
        $product->pullDomainEvents(); // drain ProductCreated (repo uses fromPersistence in production)
        return $product;
    }

    public function test_deletes_product_and_publishes_event(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $product = $this->makeProduct();

        $repository->shouldReceive('findById')->once()->with($product->id()->value())->andReturn($product);
        $repository->shouldReceive('deleteById')->once()->with($product->id()->value())->andReturnTrue();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ProductDeleted::class));

        $useCase = new DeleteProduct($repository, $eventBus);

        $useCase(new DeleteProductCommand(id: $product->id()->value()));

        $this->addToAssertionCount(1);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('deleteById');
        $eventBus->shouldNotReceive('publish');

        $useCase = new DeleteProduct($repository, $eventBus);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new DeleteProductCommand(id: 'non-existent-id'));
    }

    public function test_throws_exception_when_delete_fails(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $product = $this->makeProduct();

        $repository->shouldReceive('findById')->once()->with($product->id()->value())->andReturn($product);
        $repository->shouldReceive('deleteById')->once()->with($product->id()->value())->andReturnFalse();
        $eventBus->shouldNotReceive('publish');

        $useCase = new DeleteProduct($repository, $eventBus);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new DeleteProductCommand(id: $product->id()->value()));
    }
}
