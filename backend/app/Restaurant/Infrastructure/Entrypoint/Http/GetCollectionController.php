<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\ListRestaurants\ListRestaurants;
use Illuminate\Http\JsonResponse;

final class GetCollectionController
{
    public function __construct(
        private readonly ListRestaurants $listRestaurants,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listRestaurants)());
    }
}
