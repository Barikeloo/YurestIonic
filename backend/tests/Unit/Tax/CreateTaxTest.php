<?php

namespace Tests\Unit\Tax;

use App\Tax\Application\CreateTax\CreateTax;
use App\Tax\Application\CreateTax\CreateTaxResponse;
use App\Tax\Domain\Entity\Tax;
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

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Tax $tax): bool {
                return $tax->name() === 'IVA Test' && $tax->percentage() === 7;
            }));

        $createTax = new CreateTax($repository);
        $response = $createTax('IVA Test', 7);

        $this->assertInstanceOf(CreateTaxResponse::class, $response);
        $this->assertSame('IVA Test', $response->name);
        $this->assertSame(7, $response->percentage);
    }
}
