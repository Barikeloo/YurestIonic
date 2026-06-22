<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\LoginCustomerAccount\LoginCustomerAccount;
use App\GuestOrder\Domain\Exception\InvalidCredentialsException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\LoginCustomerAccountRequest;
use Illuminate\Http\JsonResponse;

final class LoginCustomerAccountController
{
    public function __construct(
        private readonly LoginCustomerAccount $loginCustomerAccount,
    ) {}

    public function __invoke(LoginCustomerAccountRequest $request): JsonResponse
    {
        try {
            $response = ($this->loginCustomerAccount)($request->toCommand());
        } catch (TableQrTokenNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'QR_TOKEN_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        } catch (InvalidCredentialsException $e) {
            return new JsonResponse(['error' => ['code' => 'INVALID_CREDENTIALS', 'message' => $e->getMessage()]], 401);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
