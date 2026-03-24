<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\ListSales\ListSales;
use Illuminate\Http\JsonResponse;

final class GetCollectionController
{
    public function __construct(
        private readonly ListSales $listSales,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listSales)());
    }
}
