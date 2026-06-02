<?php

namespace Tests\Unit\Product\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Product\Application\SetProductActive\SetProductActive;
use App\Product\Application\SetProductActive\SetProductActiveCommand;
use App\Product\Application\SetProductActive\SetProductActiveResponse;
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

class SetProductActiveTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_activates_product(): void
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
            active: false,
        );

        $repository->shouldReceive('findById')
            ->once()
            ->with($product->id()->value())
            ->andReturn($product);

        $repository->shouldReceive('save')
            ->once();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new SetProductActive($repository, $auditRecorder);

        $response = $useCase(new SetProductActiveCommand(
            id: $product->id()->value(),
            active: true,
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(SetProductActiveResponse::class, $response);
        $this->assertTrue($response->active);
    }

    public function test_deactivates_product(): void
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
            ->once();

        $useCase = new SetProductActive($repository, $auditRecorder);

        $response = $useCase(new SetProductActiveCommand(
            id: $product->id()->value(),
            active: false,
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(SetProductActiveResponse::class, $response);
        $this->assertFalse($response->active);
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

        $useCase = new SetProductActive($repository, $auditRecorder);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new SetProductActiveCommand(
            id: 'non-existent-id',
            active: true,
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
