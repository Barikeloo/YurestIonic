<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\JoinGuestSession\JoinGuestSession;
use App\GuestOrder\Domain\Exception\TableNotOpenException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\JoinGuestSessionRequest;
use Illuminate\Http\JsonResponse;

final class JoinGuestSessionController
{
    public function __construct(
        private readonly JoinGuestSession $joinGuestSession,
    ) {}

    public function __invoke(JoinGuestSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->joinGuestSession)($request->toCommand());
        } catch (TableQrTokenNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'QR_TOKEN_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        } catch (TableNotOpenException $e) {
            return new JsonResponse(['error' => ['code' => 'TABLE_NOT_OPEN', 'message' => $e->getMessage()]], 409);
        } catch (TableToChargeException $e) {
            return new JsonResponse(['error' => ['code' => 'TABLE_TO_CHARGE', 'message' => $e->getMessage()]], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
