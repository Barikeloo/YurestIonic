<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\ListTables\ListTables;
use Illuminate\Http\JsonResponse;

class GetCollectionController
{
    public function __construct(
        private ListTables $listTables,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listTables)());
    }
}
