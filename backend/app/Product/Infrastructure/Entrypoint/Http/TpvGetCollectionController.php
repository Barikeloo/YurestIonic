<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\ListActiveProducts\ListActiveProducts;
use Illuminate\Http\JsonResponse;

/**
 * Controller for TPV endpoint that returns only active products.
 */
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
