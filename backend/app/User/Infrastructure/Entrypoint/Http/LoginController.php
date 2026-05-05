<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\AuthenticateUser\AuthenticateUser;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    public function __construct(
        private AuthenticateUser $authenticateUser,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $response = ($this->authenticateUser)($request->toCommand());
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
