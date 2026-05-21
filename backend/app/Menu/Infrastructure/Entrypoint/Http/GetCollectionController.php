<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http;

use App\Menu\Application\ListMenus\ListMenus;
use App\Menu\Infrastructure\Entrypoint\Http\Requests\ListMenusRequest;
use Illuminate\Http\JsonResponse;

final class GetCollectionController
{
    public function __construct(
        private ListMenus $listMenus,
    ) {}

    public function __invoke(ListMenusRequest $request): JsonResponse
    {
        try {
            $response = ($this->listMenus)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(['data' => $response->toArray()]);
    }
}
