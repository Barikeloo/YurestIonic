<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
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

        // Get additional data from EloquentUser
        $persistedUser = EloquentUser::query()
            ->select('role', 'restaurant_id')
            ->where('uuid', $userId)
            ->first();

        $role = $persistedUser?->role;
        $restaurantId = null;
        $restaurantName = null;

        if ($persistedUser !== null && is_numeric($persistedUser->restaurant_id)) {
            $restaurant = EloquentRestaurant::query()
                ->select('uuid', 'name')
                ->find((int) $persistedUser->restaurant_id);

            if ($restaurant !== null) {
                $restaurantId = $restaurant->uuid;
                $restaurantName = $restaurant->name;
            }
        }

        return new JsonResponse([
            'success' => true,
            'id' => $user->id()->value(),
            'name' => $user->name(),
            'email' => $user->email()->value(),
            'role' => $role,
            'restaurant_id' => $restaurantId,
            'restaurant_name' => $restaurantName,
        ]);
    }
}
