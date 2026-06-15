<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\SetProductActive\SetProductActive;
use App\Product\Application\SetProductActive\SetProductActiveCommand;
use App\Product\Application\SetProductActive\SetProductActiveResponse;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Event\ProductActivated;
use App\Product\Domain\Event\ProductDeactivated;
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

class SetProductActiveTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_activates_product_and_publishes_ProductActivated(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
            active: false,
        );
        $product->pullDomainEvents(); // drain ProductCreated

        $repository->shouldReceive('findById')->once()->with($product->id()->value())->andReturn($product);
        $repository->shouldReceive('save')->once();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ProductActivated::class));

        $useCase = new SetProductActive($repository, $eventBus);

        $response = $useCase(new SetProductActiveCommand(id: $product->id()->value(), active: true));

        $this->assertInstanceOf(SetProductActiveResponse::class, $response);
        $this->assertTrue($response->active);
    }

    public function test_deactivates_product_and_publishes_ProductDeactivated(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
            active: true,
        );
        $product->pullDomainEvents(); // drain ProductCreated

        $repository->shouldReceive('findById')->once()->with($product->id()->value())->andReturn($product);
        $repository->shouldReceive('save')->once();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ProductDeactivated::class));

        $useCase = new SetProductActive($repository, $eventBus);

        $response = $useCase(new SetProductActiveCommand(id: $product->id()->value(), active: false));

        $this->assertInstanceOf(SetProductActiveResponse::class, $response);
        $this->assertFalse($response->active);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $useCase = new SetProductActive($repository, $eventBus);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new SetProductActiveCommand(id: 'non-existent-id', active: true));
    }
}
