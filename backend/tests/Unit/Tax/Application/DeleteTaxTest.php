<?php

namespace Tests\Unit\Tax\Application;

use App\Shared\Application\Event\EventBusInterface;
use App\Tax\Application\DeleteTax\DeleteTax;
use App\Tax\Application\DeleteTax\DeleteTaxCommand;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Event\TaxDeleted;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteTaxTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TAX_ID = '00000000-0000-4000-8000-000000000000';

    public function test_deletes_tax_and_publishes_event(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $tax = Tax::fromPersistence(
            id: self::TAX_ID,
            name: 'IVA General',
            percentage: 21,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $repository->shouldReceive('findById')->once()->with(self::TAX_ID)->andReturn($tax);
        $repository->shouldReceive('deleteById')->once()->with(self::TAX_ID)->andReturnTrue();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(TaxDeleted::class));

        $useCase = new DeleteTax($repository, $eventBus);

        $useCase(new DeleteTaxCommand(id: self::TAX_ID));
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('deleteById');
        $eventBus->shouldNotReceive('publish');

        $useCase = new DeleteTax($repository, $eventBus);

        $this->expectException(TaxNotFoundException::class);

        $useCase(new DeleteTaxCommand(id: 'non-existent-id'));
    }
}
