<?php

namespace Tests\Unit\ProductVariant\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariant;
use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariantCommand;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class UpdateProductVariantTest extends TestCase
{
    private ProductVariantRepositoryInterface&MockInterface $variantRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private UpdateProductVariant $useCase;

    protected function setUp(): void
    {
        $this->variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new UpdateProductVariant(
            $this->variantRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_updates_variant_successfully(): void
    {
        $variantId = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();

        $command = new UpdateProductVariantCommand(
            id: $variantId,
            name: 'Azul',
            price: 2000,
            stock: 20,
            active: false,
            sortOrder: 1,
            restaurantId: $restaurantId,
        );

        $variant = ProductVariant::dddCreate(
            productId: Uuid::generate(),
            name: \App\ProductVariant\Domain\ValueObject\VariantName::create('Rojo'),
            price: \App\ProductVariant\Domain\ValueObject\VariantPrice::create(1500),
            stock: \App\ProductVariant\Domain\ValueObject\VariantStock::create(10),
        );

        $this->variantRepository
            ->shouldReceive('findById')
            ->once()
            ->with($variantId)
            ->andReturn($variant);

        $this->variantRepository
            ->shouldReceive('save')
            ->once()
            ->with($variant);

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

        $response = ($this->useCase)($command);

        $this->assertIsString($response->id);
        $this->assertSame('Azul', $response->name);
        $this->assertSame(2000, $response->price);
        $this->assertSame(20, $response->stock);
        $this->assertFalse($response->active);
        $this->assertSame(1, $response->sortOrder);
    }

    public function test_throws_exception_when_variant_not_found(): void
    {
        $variantId = Uuid::generate()->value();

        $command = new UpdateProductVariantCommand(
            id: $variantId,
            name: 'Azul',
            price: 2000,
            stock: 20,
            active: true,
            sortOrder: 0,
            restaurantId: Uuid::generate()->value(),
        );

        $this->variantRepository
            ->shouldReceive('findById')
            ->once()
            ->with($variantId)
            ->andReturn(null);

        $this->variantRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(ProductVariantNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_records_audit_with_correct_slug(): void
    {
        $variantId = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();

        $command = new UpdateProductVariantCommand(
            id: $variantId,
            name: 'Azul',
            price: 2000,
            stock: 20,
            active: true,
            sortOrder: 0,
            restaurantId: $restaurantId,
        );

        $variant = ProductVariant::dddCreate(
            productId: Uuid::generate(),
            name: \App\ProductVariant\Domain\ValueObject\VariantName::create('Rojo'),
            price: \App\ProductVariant\Domain\ValueObject\VariantPrice::create(1500),
            stock: \App\ProductVariant\Domain\ValueObject\VariantStock::create(10),
        );

        $this->variantRepository
            ->shouldReceive('findById')
            ->andReturn($variant);

        $this->variantRepository
            ->shouldReceive('save')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft) use ($restaurantId): bool {
                return $draft->slug->equals(ActionSlug::create('catalog.variant_updated'))
                    && $draft->restaurantId->value() === $restaurantId;
            }));

        $response = ($this->useCase)($command);

        $this->assertSame('Azul', $response->name);
    }
}
