<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\ListProducts\ListProducts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCollectionController
{
    public function __construct(
        private ListProducts $listProducts,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $includeDeleted = $request->query('all') === 'true';

        return new JsonResponse(($this->listProducts)($includeDeleted));
    }
}
