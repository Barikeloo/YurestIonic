<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\SavePendingLines\SavePendingLines;
use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Exception\InvalidGuestLineException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\SavePendingLinesRequest;
use Illuminate\Http\JsonResponse;

final class SavePendingLinesController
{
    public function __construct(
        private readonly SavePendingLines $savePendingLines,
    ) {}

    public function __invoke(SavePendingLinesRequest $request): JsonResponse
    {
        try {
            $response = ($this->savePendingLines)($request->toCommand());
        } catch (GuestSessionNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'SESSION_REQUIRED', 'message' => $e->getMessage()]], 401);
        } catch (TableToChargeException $e) {
            return new JsonResponse(['error' => ['code' => 'TABLE_TO_CHARGE', 'message' => $e->getMessage()]], 409);
        } catch (InvalidGuestLineException $e) {
            return new JsonResponse(['error' => ['code' => 'INVALID_LINE', 'message' => $e->getMessage()]], 410);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
