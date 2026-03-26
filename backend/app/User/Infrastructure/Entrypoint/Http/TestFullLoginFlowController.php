<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestFullLoginFlowController
{
    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->query('email');
        $password = $request->query('password');

        if (! $email || ! $password) {
            return new JsonResponse([
                'error' => 'Email and password parameters required',
            ], 400);
        }

        // Step 1: Find user by email
        $user = EloquentUser::query()->where('email', $email)->first();

        if (! $user) {
            return new JsonResponse([
                'step' => 'Find user by email',
                'result' => 'FAILED',
                'error' => 'User not found',
            ], 404);
        }

        // Step 2: Verify password
        $passwordCorrect = password_verify($password, $user->password);

        if (! $passwordCorrect) {
            return new JsonResponse([
                'step' => 'Verify password',
                'result' => 'FAILED',
                'error' => 'Password incorrect',
            ], 401);
        }

        // Step 3: Simulate setting session (like LoginController does)
        $request->session()->put('auth_user_id', $user->uuid);

        // Step 4: Simulate GetMeController logic
        $persistedUser = EloquentUser::query()
            ->select('id', 'uuid', 'name', 'email', 'role', 'restaurant_id')
            ->where('uuid', $user->uuid)
            ->first();

        $restaurantName = null;
        $restaurantUuid = null;
        if ($persistedUser !== null && is_numeric($persistedUser->restaurant_id)) {
            $restaurant = EloquentRestaurant::query()
                ->select('uuid', 'name')
                ->find((int) $persistedUser->restaurant_id);

            if ($restaurant !== null) {
                $restaurantName = $restaurant->name;
                $restaurantUuid = $restaurant->uuid;
            }
        }

        return new JsonResponse([
            'success' => true,
            'step' => 'Full login flow simulation',
            'user' => [
                'id' => $persistedUser->uuid,
                'name' => $persistedUser->name,
                'email' => $persistedUser->email,
                'role' => $persistedUser->role,
            ],
            'restaurant' => [
                'restaurant_id_in_db' => $persistedUser->restaurant_id,
                'restaurant_id_numeric' => is_numeric($persistedUser->restaurant_id),
                'restaurant_uuid_to_send' => $restaurantUuid,
                'restaurant_name_to_send' => $restaurantName,
            ],
        ]);
    }
}
