<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\ValidateGuestSession\ValidateGuestSession;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\ValidateGuestSessionRequest;
use Illuminate\Http\JsonResponse;

final class ValidateGuestSessionController
{
    public function __construct(
        private readonly ValidateGuestSession $validateGuestSession,
    ) {}

    public function __invoke(ValidateGuestSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->validateGuestSession)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
