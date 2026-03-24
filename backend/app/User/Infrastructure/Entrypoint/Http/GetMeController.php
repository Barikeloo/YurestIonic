<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Domain\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetMeController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
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

        $user = $this->userRepository->findById($userId);

        if ($user === null) {
            $request->session()->forget('auth_user_id');

            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        return new JsonResponse([
            'success' => true,
            'id' => $user->id()->value(),
            'name' => $user->name(),
            'email' => $user->email()->value(),
        ]);
    }
}
