<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\ListActiveProducts\ListActiveProducts;
use Illuminate\Http\JsonResponse;

final class TpvGetCollectionController
{
    public function __construct(
        private ListActiveProducts $listActiveProducts,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listActiveProducts)());
    }
}
