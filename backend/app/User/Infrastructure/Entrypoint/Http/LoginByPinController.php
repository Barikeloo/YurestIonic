<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\AuthenticateUserByPin\AuthenticateUserByPin;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\Requests\LoginByPinRequest;
use Illuminate\Http\JsonResponse;

final class LoginByPinController
{
    public function __construct(
        private readonly AuthenticateUserByPin $authenticateUserByPin,
    ) {}

    public function __invoke(LoginByPinRequest $request): JsonResponse
    {
        try {
            $response = ($this->authenticateUserByPin)($request->toCommand());
        } catch (UserNotFoundException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (InvalidCredentialsException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['success' => false, 'message' => 'Internal error.'], 500);
        }

        $request->session()->regenerate();
        $request->session()->put('auth_user_id', $response->id);

        return new JsonResponse(array_merge(['success' => true], $response->toArray()), 200);
    }
}
