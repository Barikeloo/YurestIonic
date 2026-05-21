<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http;

use App\Menu\Application\GetMenu\GetMenu;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Infrastructure\Entrypoint\Http\Requests\GetMenuRequest;
use Illuminate\Http\JsonResponse;

final class GetController
{
    public function __construct(
        private GetMenu $getMenu,
    ) {}

    public function __invoke(GetMenuRequest $request, string $id): JsonResponse
    {
        try {
            $response = ($this->getMenu)($request->toCommand($id));
        } catch (MenuNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray());
    }
}
