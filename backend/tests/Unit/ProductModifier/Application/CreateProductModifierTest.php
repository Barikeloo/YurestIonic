<?php

namespace Tests\Unit\ProductModifier\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Application\CreateProductModifier\CreateProductModifier;
use App\ProductModifier\Application\CreateProductModifier\CreateProductModifierCommand;
use App\ProductModifier\Application\CreateProductModifier\CreateProductModifierResponse;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateProductModifierTest extends TestCase
{
    private ProductRepositoryInterface&Mockery\MockInterface $productRepository;
    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private AuditRecorderInterface&Mockery\MockInterface $auditRecorder;
    private CreateProductModifier $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);
        $this->useCase = new CreateProductModifier(
            $this->productRepository,
            $this->modifierRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_modifier_and_saves_it(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';
        $command = new CreateProductModifierCommand(
            productId: $productId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 1,
            restaurantId: '550e8400-e29b-41d4-a716-446655440001',
            userId: '550e8400-e29b-41d4-a716-446655440002',
            deviceId: 'device-001',
            ipAddress: '127.0.0.1',
        );

        $this->productRepository->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn(Mockery::mock(\App\Product\Domain\Entity\Product::class));

        $this->modifierRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (ProductModifier $modifier): bool {
                return $modifier->name()->value() === 'Extra queso'
                    && $modifier->type()->value() === 'extra'
                    && $modifier->price()->value() === 200;
            }));

        $this->auditRecorder->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft): bool {
                return $draft->slug->value() === 'catalog.modifier_created'
                    && $draft->entityType === 'product_modifier'
                    && $draft->metadata['modifier_name'] === 'Extra queso';
            }));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(CreateProductModifierResponse::class, $response);
        $this->assertSame('Extra queso', $response->name);
        $this->assertSame('extra', $response->type);
        $this->assertSame(200, $response->price);
    }

    public function test_invoke_throws_when_product_not_found(): void
    {
        $command = new CreateProductModifierCommand(
            productId: '550e8400-e29b-41d4-a716-446655440000',
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 0,
            restaurantId: '550e8400-e29b-41d4-a716-446655440001',
        );

        $this->productRepository->shouldReceive('findById')
            ->once()
            ->with($command->productId)
            ->andReturn(null);

        $this->modifierRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)($command);
    }
}
