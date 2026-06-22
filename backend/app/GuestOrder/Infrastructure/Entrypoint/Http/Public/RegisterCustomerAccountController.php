<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public;

use App\GuestOrder\Application\RegisterCustomerAccount\RegisterCustomerAccount;
use App\GuestOrder\Domain\Exception\EmailAlreadyRegisteredException;
use App\GuestOrder\Domain\Exception\TableQrTokenNotFoundException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests\RegisterCustomerAccountRequest;
use Illuminate\Http\JsonResponse;

final class RegisterCustomerAccountController
{
    public function __construct(
        private readonly RegisterCustomerAccount $registerCustomerAccount,
    ) {}

    public function __invoke(RegisterCustomerAccountRequest $request): JsonResponse
    {
        try {
            $response = ($this->registerCustomerAccount)($request->toCommand());
        } catch (TableQrTokenNotFoundException $e) {
            return new JsonResponse(['error' => ['code' => 'QR_TOKEN_NOT_FOUND', 'message' => $e->getMessage()]], 404);
        } catch (EmailAlreadyRegisteredException $e) {
            return new JsonResponse(['error' => ['code' => 'EMAIL_ALREADY_REGISTERED', 'message' => $e->getMessage()]], 409);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Internal error.']], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
