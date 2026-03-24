<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\ListOrders\ListOrders;
use Illuminate\Http\JsonResponse;

final class GetCollectionController
{
    public function __construct(
        private readonly ListOrders $listOrders,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listOrders)());
    }
}
