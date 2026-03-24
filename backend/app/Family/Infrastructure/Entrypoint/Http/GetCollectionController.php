<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\ListFamilies\ListFamilies;
use Illuminate\Http\JsonResponse;

class GetCollectionController
{
    public function __construct(
        private ListFamilies $listFamilies,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listFamilies)());
    }
}
