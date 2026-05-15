<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\ListOrderLines\ListOrderLines;
use App\Order\Infrastructure\Entrypoint\Http\Requests\ListOrderLinesRequest;
use Illuminate\Http\JsonResponse;

final class GetLinesController
{
    public function __construct(
        private readonly ListOrderLines $listOrderLines,
    ) {}

    public function __invoke(ListOrderLinesRequest $request): JsonResponse
    {
        try {
            $response = ($this->listOrderLines)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(
            array_map(static fn ($item) => $item->toArray(), $response),
            200,
        );
    }
}
