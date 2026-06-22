<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\GetGuestCart\GetGuestCart;
use App\GuestOrder\Application\GetGuestCart\GetGuestCartCommand;
use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetGuestCartController
{
    public function __construct(
        private readonly GetGuestCart $getGuestCart,
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $response = ($this->getGuestCart)(new GetGuestCartCommand(
                token: $token,
                sessionToken: (string) $request->header('X-Guest-Session', ''),
            ));
        } catch (GuestSessionNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'SESSION_REQUIRED', 'message' => $e->getMessage()]], 401);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
