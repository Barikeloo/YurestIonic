<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\AuthenticateUser\AuthenticateUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController
{
    public function __construct(
        private AuthenticateUser $authenticateUser,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $response = ($this->authenticateUser)(
            $validated['email'],
            $validated['password'],
        );

        if ($response->success) {
            $request->session()->regenerate();
            $request->session()->put('auth_user_id', $response->id);
        }

        return new JsonResponse($response->toArray(), $response->statusCode);
    }
}
