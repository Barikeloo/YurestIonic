<?php

namespace Tests\Unit\Tax;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Tax\Application\CreateTax\CreateTax;
use App\Tax\Application\CreateTax\CreateTaxCommand;
use App\Tax\Application\CreateTax\CreateTaxResponse;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Exception\TaxNameAlreadyExistsException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateTaxTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_tax_and_saves_it(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('existsByName')
            ->once()
            ->with('IVA Test')
            ->andReturn(false);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Tax $tax): bool {
                return $tax->name()->value() === 'IVA Test' && $tax->percentage()->value() === 7;
            }));

        $auditRecorder->shouldReceive('record')
            ->once();

        $createTax = new CreateTax($repository, $auditRecorder);

        $response = $createTax(new CreateTaxCommand(
            name: 'IVA Test',
            percentage: 7,
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(CreateTaxResponse::class, $response);
        $this->assertSame('IVA Test', $response->name);
        $this->assertSame(7, $response->percentage);
    }

    public function test_invoke_throws_when_tax_name_already_exists(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('existsByName')
            ->once()
            ->with('IVA Test')
            ->andReturn(true);
        $repository->shouldNotReceive('save');

        $auditRecorder->shouldNotReceive('record');

        $createTax = new CreateTax($repository, $auditRecorder);

        $this->expectException(TaxNameAlreadyExistsException::class);

        $createTax(new CreateTaxCommand(
            name: 'IVA Test',
            percentage: 7,
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
