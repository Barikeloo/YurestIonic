<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetMe\GetMe;
use App\User\Application\GetMe\GetMeResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class GetMeController
{
    public function __construct(
        private GetMe $getMe,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->session()->get('auth_user_id');

        if (! is_string($userId) || $userId === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        $response = $this->getMe->__invoke($userId);
        if ($response === null) {
            $request->session()->forget('auth_user_id');
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        return new JsonResponse($response->toArray());
    }
}
