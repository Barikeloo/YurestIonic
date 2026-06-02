<?php

namespace Tests\Unit\Tax\Application;

use App\Tax\Application\ListTaxes\ListTaxes;
use App\Tax\Application\ListTaxes\ListTaxesCommand;
use App\Tax\Application\ListTaxes\ListTaxesResponse;
use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListTaxesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_all_taxes(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);

        $tax1 = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));
        $tax2 = Tax::dddCreate(TaxName::create('IVA Reducido'), TaxPercentage::create(10));

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([$tax1, $tax2]);

        $useCase = new ListTaxes($repository);

        $response = $useCase(new ListTaxesCommand(includeDeleted: false));

        $this->assertInstanceOf(ListTaxesResponse::class, $response);
        $this->assertCount(2, $response->toArray());
        $this->assertSame('IVA General', $response->toArray()[0]['name']);
        $this->assertSame(21, $response->toArray()[0]['percentage']);
        $this->assertSame('IVA Reducido', $response->toArray()[1]['name']);
    }

    public function test_includes_deleted_when_flag_is_true(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);

        $tax1 = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));
        $tax2 = Tax::dddCreate(TaxName::create('Deleted tax'), TaxPercentage::create(7));

        $repository->shouldReceive('findAll')
            ->once()
            ->with(true)
            ->andReturn([$tax1, $tax2]);

        $useCase = new ListTaxes($repository);

        $response = $useCase(new ListTaxesCommand(includeDeleted: true));

        $this->assertCount(2, $response->toArray());
    }

    public function test_returns_empty_list(): void
    {
        $repository = Mockery::mock(TaxRepositoryInterface::class);

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([]);

        $useCase = new ListTaxes($repository);

        $response = $useCase(new ListTaxesCommand(includeDeleted: false));

        $this->assertInstanceOf(ListTaxesResponse::class, $response);
        $this->assertEmpty($response->toArray());
    }
}
