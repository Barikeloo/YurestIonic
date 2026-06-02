<?php

namespace Tests\Unit\Tax\Application;

use App\Tax\Application\GetTax\GetTax;
use App\Tax\Application\GetTax\GetTaxCommand;
use App\Tax\Application\GetTax\GetTaxResponse;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Exception\TaxNotFoundException;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetTaxTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_tax_when_found(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);

        $tax = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));

        $repository->shouldReceive('findById')
            ->once()
            ->with($tax->id()->value())
            ->andReturn($tax);

        $useCase = new GetTax($repository);

        $response = $useCase(new GetTaxCommand(id: $tax->id()->value()));

        $this->assertInstanceOf(GetTaxResponse::class, $response);
        $this->assertSame('IVA General', $response->name);
        $this->assertSame(21, $response->percentage);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $useCase = new GetTax($repository);

        $this->expectException(TaxNotFoundException::class);

        $useCase(new GetTaxCommand(id: 'non-existent-id'));
    }
}
