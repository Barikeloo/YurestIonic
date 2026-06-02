<?php

namespace Tests\Unit\Tax\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Tax\Application\DeleteTax\DeleteTax;
use App\Tax\Application\DeleteTax\DeleteTaxCommand;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use Mockery;
use PHPUnit\Framework\TestCase;

class DeleteTaxTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_deletes_tax(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $tax = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));

        $repository->shouldReceive('findById')
            ->once()
            ->with($tax->id()->value())
            ->andReturn($tax);

        $repository->shouldReceive('deleteById')
            ->once()
            ->with($tax->id()->value())
            ->andReturnTrue();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new DeleteTax($repository, $auditRecorder);

        $useCase(new DeleteTaxCommand(
            id: $tax->id()->value(),
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->addToAssertionCount(1);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('deleteById');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new DeleteTax($repository, $auditRecorder);

        $this->expectException(TaxNotFoundException::class);

        $useCase(new DeleteTaxCommand(
            id: 'non-existent-id',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
