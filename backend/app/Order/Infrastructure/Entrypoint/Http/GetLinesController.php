<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\ListOrderLines\ListOrderLines;
use Illuminate\Http\JsonResponse;

final class GetLinesController
{
    public function __construct(
        private readonly ListOrderLines $listOrderLines,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        return new JsonResponse(($this->listOrderLines)($id));
    }
}
