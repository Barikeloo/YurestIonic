<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\ListCashMovements\ListCashMovements;
use App\Cash\Application\ListCashMovements\ListCashMovementsCommand;
use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ListCashMovementsTest extends TestCase
{
    private CashMovementRepositoryInterface&MockInterface $movementRepository;
    private ListCashMovements $useCase;

    protected function setUp(): void
    {
        $this->movementRepository = Mockery::mock(CashMovementRepositoryInterface::class);
        $this->useCase = new ListCashMovements($this->movementRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_lists_movements(): void
    {
        $sessionId = Uuid::generate()->value();

        $command = new ListCashMovementsCommand(cashSessionId: $sessionId);

        $movement1 = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            cashSessionId: Uuid::create($sessionId),
            type: MovementType::in(),
            reasonCode: MovementReasonCode::changeRefill(),
            amount: Money::create(20000),
            userId: Uuid::generate(),
        );

        $movement2 = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            cashSessionId: Uuid::create($sessionId),
            type: MovementType::out(),
            reasonCode: MovementReasonCode::sangria(),
            amount: Money::create(5000),
            userId: Uuid::generate(),
        );

        $this->movementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->with(Mockery::on(fn(Uuid $id): bool => $id->value() === $sessionId))
            ->andReturn([$movement1, $movement2]);

        $response = ($this->useCase)($command);

        $this->assertCount(2, $response->movements);
        $this->assertSame('change_refill', $response->movements[0]->reasonCode);
    }

    public function test_returns_empty_array_when_no_movements(): void
    {
        $command = new ListCashMovementsCommand(
            cashSessionId: Uuid::generate()->value(),
        );

        $this->movementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertEmpty($response->movements);
    }
}
