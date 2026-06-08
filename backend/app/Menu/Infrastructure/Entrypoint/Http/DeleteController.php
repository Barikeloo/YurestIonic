<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http;

use App\Menu\Application\ArchiveMenu\ArchiveMenu;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Infrastructure\Entrypoint\Http\Requests\ArchiveMenuRequest;
use Illuminate\Http\JsonResponse;

final class DeleteController
{
    public function __construct(
        private ArchiveMenu $archiveMenu,
    ) {}

    public function __invoke(ArchiveMenuRequest $request, string $id): JsonResponse
    {
        try {
            ($this->archiveMenu)($request->toCommand($id));
        } catch (MenuNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(null, 204);
    }
}
