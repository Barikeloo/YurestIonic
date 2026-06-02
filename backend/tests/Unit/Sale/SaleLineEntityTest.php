<?php

namespace Tests\Unit\Sale;

use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\ValueObject\SaleLinePrice;
use App\Sale\Domain\ValueObject\SaleLineQuantity;
use App\Sale\Domain\ValueObject\SaleLineTaxPercentage;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class SaleLineEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_value_objects(): void
    {
        $id = Uuid::generate();
        $restaurantId = Uuid::generate();
        $saleId = Uuid::generate();
        $orderLineId = Uuid::generate();
        $productId = Uuid::generate();
        $userId = Uuid::generate();

        $saleLine = SaleLine::dddCreate(
            id: $id,
            restaurantId: $restaurantId,
            saleId: $saleId,
            orderLineId: $orderLineId,
            productId: $productId,
            userId: $userId,
            quantity: SaleLineQuantity::create(2),
            price: SaleLinePrice::create(1500),
            taxPercentage: SaleLineTaxPercentage::create(10),
        );

        $this->assertSame($id->value(), $saleLine->id()->value());
        $this->assertSame($restaurantId->value(), $saleLine->restaurantId()->value());
        $this->assertSame($saleId->value(), $saleLine->saleId()->value());
        $this->assertSame($orderLineId->value(), $saleLine->orderLineId()->value());
        $this->assertSame($productId->value(), $saleLine->productId()->value());
        $this->assertSame($userId->value(), $saleLine->userId()->value());
        $this->assertSame(2, $saleLine->quantity()->value());
        $this->assertSame(1500, $saleLine->price()->value());
        $this->assertSame(10, $saleLine->taxPercentage()->value());
    }
}
