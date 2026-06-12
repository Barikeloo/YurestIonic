<?php

namespace Tests\Unit\Tax;

use App\Shared\Application\Event\EventBusInterface;
use App\Tax\Application\CreateTax\CreateTax;
use App\Tax\Application\CreateTax\CreateTaxCommand;
use App\Tax\Application\CreateTax\CreateTaxResponse;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Event\TaxCreated;
use App\Tax\Domain\Exception\TaxNameAlreadyExistsException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateTaxTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_invoke_creates_tax_and_publishes_event(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('existsByName')->once()->with('IVA Test')->andReturn(false);

        $repository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Tax $tax): bool => $tax->name()->value() === 'IVA Test' && $tax->percentage()->value() === 7
        ));

        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(TaxCreated::class));

        $createTax = new CreateTax($repository, $eventBus);

        $response = $createTax(new CreateTaxCommand(name: 'IVA Test', percentage: 7));

        $this->assertInstanceOf(CreateTaxResponse::class, $response);
        $this->assertSame('IVA Test', $response->name);
        $this->assertSame(7, $response->percentage);
    }

    public function test_invoke_throws_when_tax_name_already_exists(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('existsByName')->once()->with('IVA Test')->andReturn(true);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $createTax = new CreateTax($repository, $eventBus);

        $this->expectException(TaxNameAlreadyExistsException::class);

        $createTax(new CreateTaxCommand(name: 'IVA Test', percentage: 7));
    }
}
