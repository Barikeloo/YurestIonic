<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\GetTableStatus\GetTableStatus;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\GetTableStatusRequest;
use Illuminate\Http\JsonResponse;

final class GetTableStatusController
{
    public function __construct(
        private readonly GetTableStatus $getTableStatus,
    ) {}

    public function __invoke(GetTableStatusRequest $request): JsonResponse
    {
        try {
            $response = ($this->getTableStatus)($request->toCommand());
        } catch (TableQrTokenNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
