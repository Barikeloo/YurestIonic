<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http;

use App\Menu\Application\CreateMenu\CreateMenu;
use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Infrastructure\Entrypoint\Http\Requests\CreateMenuRequest;
use Illuminate\Http\JsonResponse;

final class PostController
{
    public function __construct(
        private CreateMenu $createMenu,
    ) {}

    public function __invoke(CreateMenuRequest $request): JsonResponse
    {
        try {
            $response = ($this->createMenu)($request->toCommand());
        } catch (MenuInvalidConfigurationException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
