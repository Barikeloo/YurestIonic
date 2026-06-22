<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\DeletePendingLine\DeletePendingLine;
use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Exception\InvalidGuestLineException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\DeletePendingLineRequest;
use Illuminate\Http\JsonResponse;

final class DeletePendingLineController
{
    public function __construct(
        private readonly DeletePendingLine $deletePendingLine,
    ) {}

    public function __invoke(DeletePendingLineRequest $request): JsonResponse
    {
        try {
            $response = ($this->deletePendingLine)($request->toCommand());
        } catch (GuestSessionNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'SESSION_REQUIRED', 'message' => $e->getMessage()]], 401);
        } catch (InvalidGuestLineException $e) {
            return new JsonResponse(['error' => ['code' => 'INVALID_LINE', 'message' => $e->getMessage()]], 422);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
