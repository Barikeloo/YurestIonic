<?php

namespace Tests\Unit\Tax\Application;

use App\Shared\Application\Event\EventBusInterface;
use App\Tax\Application\UpdateTax\UpdateTax;
use App\Tax\Application\UpdateTax\UpdateTaxCommand;
use App\Tax\Application\UpdateTax\UpdateTaxResponse;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Event\TaxUpdated;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateTaxTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const TAX_ID = '00000000-0000-4000-8000-000000000000';

    private function existingTax(): Tax
    {
        // fromPersistence (not dddCreate) so the entity starts with no recorded
        // events, mirroring a tax loaded from the repository.
        return Tax::fromPersistence(
            id: self::TAX_ID,
            name: 'IVA General',
            percentage: 21,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function test_updates_tax_name_and_publishes_event(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);
        $tax = $this->existingTax();

        $repository->shouldReceive('findById')->once()->with(self::TAX_ID)->andReturn($tax);
        $repository->shouldReceive('save')->once();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(TaxUpdated::class));

        $useCase = new UpdateTax($repository, $eventBus);

        $response = $useCase(new UpdateTaxCommand(id: self::TAX_ID, name: 'IVA Reducido'));

        $this->assertInstanceOf(UpdateTaxResponse::class, $response);
        $this->assertSame('IVA Reducido', $response->name);
        $this->assertSame(21, $response->percentage);
    }

    public function test_updates_tax_percentage_and_publishes_event(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);
        $tax = $this->existingTax();

        $repository->shouldReceive('findById')->once()->with(self::TAX_ID)->andReturn($tax);
        $repository->shouldReceive('save')->once();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(TaxUpdated::class));

        $useCase = new UpdateTax($repository, $eventBus);

        $response = $useCase(new UpdateTaxCommand(id: self::TAX_ID, percentage: 10));

        $this->assertSame('IVA General', $response->name);
        $this->assertSame(10, $response->percentage);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $useCase = new UpdateTax($repository, $eventBus);

        $this->expectException(TaxNotFoundException::class);

        $useCase(new UpdateTaxCommand(id: 'non-existent-id', name: 'IVA'));
    }

    public function test_publishes_no_event_when_nothing_changes(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);
        $tax = $this->existingTax();

        $repository->shouldReceive('findById')->once()->with(self::TAX_ID)->andReturn($tax);
        $repository->shouldReceive('save')->once();
        // No fields provided -> entity records nothing -> publish() called with no events.
        $eventBus->shouldReceive('publish')->once()->withNoArgs();

        $useCase = new UpdateTax($repository, $eventBus);

        $response = $useCase(new UpdateTaxCommand(id: self::TAX_ID));

        $this->assertInstanceOf(UpdateTaxResponse::class, $response);
    }
}
