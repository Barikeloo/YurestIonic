<?php

namespace Tests\Unit\Product\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Product\Application\DeleteProduct\DeleteProduct;
use App\Product\Application\DeleteProduct\DeleteProductCommand;
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

class DeleteProductTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_deletes_product(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Coca Cola'),
            price: ProductPrice::create(250),
            stock: ProductStock::create(10),
        );

        $repository->shouldReceive('findById')
            ->once()
            ->with($product->id()->value())
            ->andReturn($product);

        $repository->shouldReceive('deleteById')
            ->once()
            ->with($product->id()->value())
            ->andReturnTrue();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new DeleteProduct($repository, $auditRecorder);

        $useCase(new DeleteProductCommand(
            id: $product->id()->value(),
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

        $repository->shouldNotReceive('deleteById');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new DeleteProduct($repository, $auditRecorder);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new DeleteProductCommand(
            id: 'non-existent-id',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }

    public function test_throws_exception_when_delete_fails(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Coca Cola'),
            price: ProductPrice::create(250),
            stock: ProductStock::create(10),
        );

        $repository->shouldReceive('findById')
            ->once()
            ->with($product->id()->value())
            ->andReturn($product);

        $repository->shouldReceive('deleteById')
            ->once()
            ->with($product->id()->value())
            ->andReturnFalse();

        $auditRecorder->shouldNotReceive('record');

        $useCase = new DeleteProduct($repository, $auditRecorder);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new DeleteProductCommand(
            id: $product->id()->value(),
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
