<?php

namespace App\Zone\Infrastructure\Entrypoint\Http;

use App\Zone\Application\ListZones\ListZones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCollectionController
{
    public function __construct(
        private ListZones $listZones,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $includeDeleted = $request->query('all') === 'true';

        return new JsonResponse(($this->listZones)($includeDeleted));
    }
}
