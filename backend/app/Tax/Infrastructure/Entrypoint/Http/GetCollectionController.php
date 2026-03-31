<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\ListTaxes\ListTaxes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCollectionController
{
    public function __construct(
        private ListTaxes $listTaxes,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $includeDeleted = $request->query('all') === 'true';

        return new JsonResponse(($this->listTaxes)($includeDeleted));
    }
}
