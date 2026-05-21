<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http;

use App\Menu\Application\UpdateMenu\UpdateMenu;
use App\Menu\Domain\Exception\MenuArchivedException;
use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Infrastructure\Entrypoint\Http\Requests\UpdateMenuRequest;
use Illuminate\Http\JsonResponse;

final class PutController
{
    public function __construct(
        private UpdateMenu $updateMenu,
    ) {}

    public function __invoke(UpdateMenuRequest $request, string $id): JsonResponse
    {
        try {
            $response = ($this->updateMenu)($request->toCommand($id));
        } catch (MenuNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (MenuArchivedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (MenuInvalidConfigurationException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray());
    }
}
