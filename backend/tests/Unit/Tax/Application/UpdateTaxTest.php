<?php

namespace Tests\Unit\Tax\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Tax\Application\UpdateTax\UpdateTax;
use App\Tax\Application\UpdateTax\UpdateTaxCommand;
use App\Tax\Application\UpdateTax\UpdateTaxResponse;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use Mockery;
use PHPUnit\Framework\TestCase;

class UpdateTaxTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_updates_tax_name(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $tax = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));

        $repository->shouldReceive('findById')
            ->once()
            ->with($tax->id()->value())
            ->andReturn($tax);

        $repository->shouldReceive('save')
            ->once();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new UpdateTax($repository, $auditRecorder);

        $response = $useCase(new UpdateTaxCommand(
            id: $tax->id()->value(),
            name: 'IVA Reducido',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(UpdateTaxResponse::class, $response);
        $this->assertSame('IVA Reducido', $response->name);
        $this->assertSame(21, $response->percentage);
    }

    public function test_updates_tax_percentage(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $tax = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));

        $repository->shouldReceive('findById')
            ->once()
            ->with($tax->id()->value())
            ->andReturn($tax);

        $repository->shouldReceive('save')
            ->once();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new UpdateTax($repository, $auditRecorder);

        $response = $useCase(new UpdateTaxCommand(
            id: $tax->id()->value(),
            percentage: 10,
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(UpdateTaxResponse::class, $response);
        $this->assertSame('IVA General', $response->name);
        $this->assertSame(10, $response->percentage);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('save');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new UpdateTax($repository, $auditRecorder);

        $this->expectException(TaxNotFoundException::class);

        $useCase(new UpdateTaxCommand(
            id: 'non-existent-id',
            name: 'IVA',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }

    public function test_skips_audit_when_no_changes(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $tax = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));

        $repository->shouldReceive('findById')
            ->once()
            ->with($tax->id()->value())
            ->andReturn($tax);

        $repository->shouldReceive('save')
            ->once();

        $auditRecorder->shouldNotReceive('record');

        $useCase = new UpdateTax($repository, $auditRecorder);

        $response = $useCase(new UpdateTaxCommand(
            id: $tax->id()->value(),
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(UpdateTaxResponse::class, $response);
    }
}
