<?php

namespace Tests\Unit\Product\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Product\Application\UpdateProduct\UpdateProduct;
use App\Product\Application\UpdateProduct\UpdateProductCommand;
use App\Product\Application\UpdateProduct\UpdateProductResponse;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use PHPUnit\Framework\TestCase;

class UpdateProductTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_updates_product_fields(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create('/images/old.png'),
            name: ProductName::create('Old Name'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(1),
        );

        $repository->shouldReceive('findById')
            ->once()
            ->with($product->id()->value())
            ->andReturn($product);

        $repository->shouldReceive('save')
            ->once();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new UpdateProduct($repository, $auditRecorder);

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
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(UpdateProductResponse::class, $response);
        $this->assertSame('New Name', $response->name);
        $this->assertFalse($response->active);
    }

    public function test_records_price_changed_when_price_differs(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );

        $repository->shouldReceive('findById')
            ->once()
            ->with($product->id()->value())
            ->andReturn($product);

        $repository->shouldReceive('save')
            ->once();

        $auditRecorder->shouldReceive('record')
            ->twice();

        $useCase = new UpdateProduct($repository, $auditRecorder);

        $useCase(new UpdateProductCommand(
            id: $product->id()->value(),
            familyId: '00000000-0000-4000-8000-000000000001',
            taxId: '00000000-0000-4000-8000-000000000002',
            imageSrc: null,
            name: 'Test',
            price: 200,
            stock: 5,
            active: true,
            allergens: [],
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->addToAssertionCount(1);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('save');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new UpdateProduct($repository, $auditRecorder);

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
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
