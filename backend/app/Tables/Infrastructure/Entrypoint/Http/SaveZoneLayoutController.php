<?php

declare(strict_types=1);

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\SaveZoneLayout\SaveZoneLayout;
use App\Tables\Infrastructure\Entrypoint\Http\Requests\SaveZoneLayoutRequest;
use Illuminate\Http\JsonResponse;

final class SaveZoneLayoutController
{
    public function __construct(
        private readonly SaveZoneLayout $saveZoneLayout,
    ) {}

    public function __invoke(SaveZoneLayoutRequest $request, string $id): JsonResponse
    {
        try {
            $response = ($this->saveZoneLayout)($request->toCommand($id));
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray());
    }
}
