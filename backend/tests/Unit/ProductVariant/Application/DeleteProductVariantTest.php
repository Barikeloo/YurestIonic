<?php

namespace Tests\Unit\ProductVariant\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\ProductVariant\Application\DeleteProductVariant\DeleteProductVariant;
use App\ProductVariant\Application\DeleteProductVariant\DeleteProductVariantCommand;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class DeleteProductVariantTest extends TestCase
{
    private ProductVariantRepositoryInterface&MockInterface $variantRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private DeleteProductVariant $useCase;

    protected function setUp(): void
    {
        $this->variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new DeleteProductVariant(
            $this->variantRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_deletes_variant_successfully(): void
    {
        $variantId = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();

        $command = new DeleteProductVariantCommand(
            id: $variantId,
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
            ->shouldReceive('deleteById')
            ->once()
            ->with($variantId);

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

        ($this->useCase)($command);

        $this->assertTrue(true);
    }

    public function test_throws_exception_when_variant_not_found(): void
    {
        $variantId = Uuid::generate()->value();

        $command = new DeleteProductVariantCommand(
            id: $variantId,
            restaurantId: Uuid::generate()->value(),
        );

        $this->variantRepository
            ->shouldReceive('findById')
            ->once()
            ->with($variantId)
            ->andReturn(null);

        $this->variantRepository->shouldNotReceive('deleteById');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(ProductVariantNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_records_audit_with_correct_slug(): void
    {
        $variantId = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();

        $command = new DeleteProductVariantCommand(
            id: $variantId,
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
            ->shouldReceive('deleteById')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft) use ($restaurantId): bool {
                return $draft->slug->equals(ActionSlug::create('catalog.variant_deleted'))
                    && $draft->restaurantId->value() === $restaurantId;
            }));

        ($this->useCase)($command);

        $this->assertTrue(true);
    }
}
