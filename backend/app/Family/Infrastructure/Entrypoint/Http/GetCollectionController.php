<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\ListFamilies\ListFamilies;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCollectionController
{
    public function __construct(
        private ListFamilies $listFamilies,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $includeDeleted = $request->query('all') === 'true';

        return new JsonResponse(($this->listFamilies)($includeDeleted));
    }
}
