<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\ListProducts\ListProducts;
use Illuminate\Http\JsonResponse;

class GetCollectionController
{
    public function __construct(
        private ListProducts $listProducts,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listProducts)());
    }
}
