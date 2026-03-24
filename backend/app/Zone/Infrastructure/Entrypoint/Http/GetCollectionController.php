<?php

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\ListZones\ListZones;
use Illuminate\Http\JsonResponse;

class GetCollectionController
{
    public function __construct(
        private ListZones $listZones,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listZones)());
    }
}
