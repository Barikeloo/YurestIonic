<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\GetGuestOrdersHistory\GetGuestOrdersHistory;
use App\GuestOrder\Application\GetGuestOrdersHistory\GetGuestOrdersHistoryCommand;
use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetGuestOrdersHistoryController
{
    public function __construct(
        private readonly GetGuestOrdersHistory $getGuestOrdersHistory,
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $response = ($this->getGuestOrdersHistory)(new GetGuestOrdersHistoryCommand(
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
