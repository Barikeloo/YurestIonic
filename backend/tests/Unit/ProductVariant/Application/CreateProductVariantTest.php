<?php

namespace Tests\Unit\ProductVariant\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductVariant\Application\CreateProductVariant\CreateProductVariant;
use App\ProductVariant\Application\CreateProductVariant\CreateProductVariantCommand;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\ProductVariant\Domain\ValueObject\VariantName;
use App\ProductVariant\Domain\ValueObject\VariantPrice;
use App\ProductVariant\Domain\ValueObject\VariantStock;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CreateProductVariantTest extends TestCase
{
    private ProductRepositoryInterface&MockInterface $productRepository;
    private ProductVariantRepositoryInterface&MockInterface $variantRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private CreateProductVariant $useCase;

    protected function setUp(): void
    {
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new CreateProductVariant(
            $this->productRepository,
            $this->variantRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_creates_variant_successfully(): void
    {
        $productId = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $userId = Uuid::generate()->value();
        $deviceId = 'device-1';
        $ipAddress = '127.0.0.1';

        $command = new CreateProductVariantCommand(
            productId: $productId,
            name: 'Rojo',
            price: 1500,
            stock: 10,
            active: true,
            sortOrder: 0,
            restaurantId: $restaurantId,
            userId: $userId,
            deviceId: $deviceId,
            ipAddress: $ipAddress,
        );

        $product = Mockery::mock(Product::class);
        $this->productRepository
            ->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn($product);

        $this->variantRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(ProductVariant::class));

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

        $response = ($this->useCase)($command);

        $this->assertSame($productId, $response->productId);
        $this->assertSame('Rojo', $response->name);
        $this->assertSame(1500, $response->price);
        $this->assertSame(10, $response->stock);
        $this->assertTrue($response->active);
        $this->assertSame(0, $response->sortOrder);
    }

    public function test_throws_exception_when_product_not_found(): void
    {
        $productId = Uuid::generate()->value();

        $command = new CreateProductVariantCommand(
            productId: $productId,
            name: 'Rojo',
            price: 1500,
            stock: 10,
            active: true,
            sortOrder: 0,
            restaurantId: Uuid::generate()->value(),
        );

        $this->productRepository
            ->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn(null);

        $this->variantRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_records_audit_with_correct_slug(): void
    {
        $productId = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();

        $command = new CreateProductVariantCommand(
            productId: $productId,
            name: 'Azul',
            price: 2000,
            stock: 5,
            active: true,
            sortOrder: 0,
            restaurantId: $restaurantId,
        );

        $product = Mockery::mock(Product::class);
        $this->productRepository
            ->shouldReceive('findById')
            ->andReturn($product);

        $this->variantRepository
            ->shouldReceive('save')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft) use ($restaurantId): bool {
                return $draft->slug->equals(ActionSlug::create('catalog.variant_created'))
                    && $draft->restaurantId->value() === $restaurantId;
            }));

        $response = ($this->useCase)($command);

        $this->assertSame('Azul', $response->name);
    }
}
