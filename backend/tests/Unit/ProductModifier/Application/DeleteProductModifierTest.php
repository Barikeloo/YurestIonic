<?php

namespace Tests\Unit\ProductModifier\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\ProductModifier\Application\DeleteProductModifier\DeleteProductModifier;
use App\ProductModifier\Application\DeleteProductModifier\DeleteProductModifierCommand;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class DeleteProductModifierTest extends TestCase
{
    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private AuditRecorderInterface&Mockery\MockInterface $auditRecorder;
    private DeleteProductModifier $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);
        $this->useCase = new DeleteProductModifier(
            $this->modifierRepository,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_deletes_modifier_and_records_audit(): void
    {
        $modifierId = '550e8400-e29b-41d4-a716-446655440000';
        $productId = '550e8400-e29b-41d4-a716-446655440001';
        $existingModifier = ProductModifier::fromPersistence(
            id: $modifierId,
            productId: $productId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 1,
            createdAt: new \DateTimeImmutable('-1 day'),
            updatedAt: new \DateTimeImmutable('-1 day'),
        );

        $command = new DeleteProductModifierCommand(
            id: $modifierId,
            restaurantId: '550e8400-e29b-41d4-a716-446655440002',
            userId: '550e8400-e29b-41d4-a716-446655440003',
            deviceId: 'device-001',
            ipAddress: '127.0.0.1',
        );

        $this->modifierRepository->shouldReceive('findById')
            ->once()
            ->with($modifierId)
            ->andReturn($existingModifier);

        $this->auditRecorder->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft) use ($modifierId): bool {
                return $draft->slug->value() === 'catalog.modifier_deleted'
                    && $draft->entityId === $modifierId
                    && $draft->before['name'] === 'Extra queso'
                    && $draft->before['type'] === 'extra';
            }));

        $this->modifierRepository->shouldReceive('deleteById')
            ->once()
            ->with($modifierId)
            ->andReturn(true);

        ($this->useCase)($command);

        $this->assertTrue(true);
    }

    public function test_invoke_throws_when_modifier_not_found(): void
    {
        $command = new DeleteProductModifierCommand(
            id: '550e8400-e29b-41d4-a716-446655440000',
            restaurantId: '550e8400-e29b-41d4-a716-446655440001',
        );

        $this->modifierRepository->shouldReceive('findById')
            ->once()
            ->with($command->id)
            ->andReturn(null);

        $this->auditRecorder->shouldNotReceive('record');
        $this->modifierRepository->shouldNotReceive('deleteById');

        $this->expectException(ProductModifierNotFoundException::class);

        ($this->useCase)($command);
    }
}
