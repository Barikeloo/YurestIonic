<?php

namespace Tests\Unit\Product\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Product\Application\CreateProduct\CreateProduct;
use App\Product\Application\CreateProduct\CreateProductCommand;
use App\Product\Application\CreateProduct\CreateProductResponse;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateProductTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_invoke_creates_product_and_saves_it(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Product $product): bool {
                return $product->name()->value() === 'Coca Cola'
                    && $product->price()->value() === 250
                    && $product->stock()->value() === 10;
            }));

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new CreateProduct($repository, $auditRecorder);

        $response = $useCase(new CreateProductCommand(
            familyId: '00000000-0000-4000-8000-000000000001',
            taxId: '00000000-0000-4000-8000-000000000002',
            imageSrc: '/images/coke.png',
            name: 'Coca Cola',
            price: 250,
            stock: 10,
            active: true,
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(CreateProductResponse::class, $response);
        $this->assertSame('Coca Cola', $response->name);
        $this->assertSame(250, $response->price);
    }
}
