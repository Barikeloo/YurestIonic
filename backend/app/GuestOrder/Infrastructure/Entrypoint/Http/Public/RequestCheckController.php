<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\RequestCheck\RequestCheck;
use App\GuestOrder\Application\RequestCheck\RequestCheckCommand;
use App\GuestOrder\Domain\Exception\GuestSessionNotFoundException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RequestCheckController
{
    public function __construct(
        private readonly RequestCheck $requestCheck,
    ) {}

    public function __invoke(Request $request, string $token): JsonResponse
    {
        try {
            $response = ($this->requestCheck)(new RequestCheckCommand(
                token: $token,
                sessionToken: (string) $request->header('X-Guest-Session', ''),
            ));
        } catch (GuestSessionNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'SESSION_REQUIRED', 'message' => $e->getMessage()]], 401);
        } catch (TableToChargeException $e) {
            return new JsonResponse(['error' => ['code' => 'TABLE_TO_CHARGE', 'message' => $e->getMessage()]], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
