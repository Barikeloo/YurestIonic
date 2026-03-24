<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\ListTaxes\ListTaxes;
use Illuminate\Http\JsonResponse;

class GetCollectionController
{
    public function __construct(
        private ListTaxes $listTaxes,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listTaxes)());
    }
}
