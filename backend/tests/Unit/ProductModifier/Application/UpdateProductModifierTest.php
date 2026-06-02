<?php

namespace Tests\Unit\ProductModifier\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\ProductModifier\Application\UpdateProductModifier\UpdateProductModifier;
use App\ProductModifier\Application\UpdateProductModifier\UpdateProductModifierCommand;
use App\ProductModifier\Application\UpdateProductModifier\UpdateProductModifierResponse;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use PHPUnit\Framework\TestCase;

class UpdateProductModifierTest extends TestCase
{
    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private AuditRecorderInterface&Mockery\MockInterface $auditRecorder;
    private UpdateProductModifier $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);
        $this->useCase = new UpdateProductModifier(
            $this->modifierRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_updates_modifier_and_saves_it(): void
    {
        $modifierId = '550e8400-e29b-41d4-a716-446655440000';
        $productId = '550e8400-e29b-41d4-a716-446655440001';
        $existingModifier = ProductModifier::fromPersistence(
            id: $modifierId,
            productId: $productId,
            name: 'Patatas fritas',
            type: 'accompaniment',
            isRequired: true,
            selectionType: 'single',
            price: 0,
            active: true,
            sortOrder: 1,
            createdAt: new \DateTimeImmutable('-1 day'),
            updatedAt: new \DateTimeImmutable('-1 day'),
        );

        $command = new UpdateProductModifierCommand(
            id: $modifierId,
            name: 'Aros de cebolla',
            type: 'extra',
            isRequired: false,
            selectionType: 'multi',
            price: 300,
            active: false,
            sortOrder: 2,
            restaurantId: '550e8400-e29b-41d4-a716-446655440002',
            userId: '550e8400-e29b-41d4-a716-446655440003',
            deviceId: 'device-001',
            ipAddress: '127.0.0.1',
        );

        $this->modifierRepository->shouldReceive('findById')
            ->once()
            ->with($modifierId)
            ->andReturn($existingModifier);

        $this->modifierRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (ProductModifier $modifier): bool {
                return $modifier->name()->value() === 'Aros de cebolla'
                    && $modifier->type()->value() === 'extra'
                    && $modifier->price()->value() === 300;
            }));

        $this->auditRecorder->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft): bool {
                return $draft->slug->value() === 'catalog.modifier_updated'
                    && $draft->before['name'] === 'Patatas fritas'
                    && $draft->after['name'] === 'Aros de cebolla'
                    && $draft->metadata['modifier_name'] === 'Aros de cebolla';
            }));

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(UpdateProductModifierResponse::class, $response);
        $this->assertSame('Aros de cebolla', $response->name);
        $this->assertSame(300, $response->price);
    }

    public function test_invoke_throws_when_modifier_not_found(): void
    {
        $command = new UpdateProductModifierCommand(
            id: '550e8400-e29b-41d4-a716-446655440000',
            name: 'Aros de cebolla',
            type: 'extra',
            isRequired: false,
            selectionType: 'multi',
            price: 300,
            active: false,
            sortOrder: 0,
            restaurantId: '550e8400-e29b-41d4-a716-446655440001',
        );

        $this->modifierRepository->shouldReceive('findById')
            ->once()
            ->with($command->id)
            ->andReturn(null);

        $this->modifierRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(ProductModifierNotFoundException::class);

        ($this->useCase)($command);
    }
}
