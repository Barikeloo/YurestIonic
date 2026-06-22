<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\OpenTableByGuest\OpenTableByGuest;
use App\GuestOrder\Domain\Exception\TableAlreadyOpenException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Domain\Exception\TableToChargeException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\OpenTableByGuestRequest;
use Illuminate\Http\JsonResponse;

final class OpenTableByGuestController
{
    public function __construct(
        private readonly OpenTableByGuest $openTableByGuest,
    ) {}

    public function __invoke(OpenTableByGuestRequest $request): JsonResponse
    {
        try {
            $response = ($this->openTableByGuest)($request->toCommand());
        } catch (TableQrTokenNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'QR_TOKEN_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        } catch (TableToChargeException $e) {
            return new JsonResponse(['error' => ['code' => 'TABLE_TO_CHARGE', 'message' => $e->getMessage()]], 409);
        } catch (TableAlreadyOpenException $e) {
            return new JsonResponse(['error' => ['code' => 'TABLE_ALREADY_OPEN', 'message' => $e->getMessage()]], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
