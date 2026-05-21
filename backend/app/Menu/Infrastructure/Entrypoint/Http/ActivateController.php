<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http;

use App\Menu\Application\SetMenuActive\SetMenuActive;
use App\Menu\Domain\Exception\MenuArchivedException;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Infrastructure\Entrypoint\Http\Requests\SetMenuActiveRequest;
use Illuminate\Http\JsonResponse;

final class ActivateController
{
    public function __construct(
        private SetMenuActive $setMenuActive,
    ) {}

    public function __invoke(SetMenuActiveRequest $request, string $id): JsonResponse
    {
        try {
            $response = ($this->setMenuActive)($request->toCommand($id, true));
        } catch (MenuNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (MenuArchivedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray());
    }
}
