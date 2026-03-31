<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\ListTables\ListTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetCollectionController
{
    public function __construct(
        private ListTables $listTables,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $includeDeleted = $request->query('all') === 'true';

        return new JsonResponse(($this->listTables)($includeDeleted));
    }
}
