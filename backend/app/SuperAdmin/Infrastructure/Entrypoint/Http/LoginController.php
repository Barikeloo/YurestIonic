<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class LoginController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $superAdmin = EloquentSuperAdmin::query()
            ->where('email', $validated['email'])
            ->first();

        if ($superAdmin === null || ! Hash::check($validated['password'], $superAdmin->password)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $request->session()->regenerate();
        $request->session()->put('super_admin_id', $superAdmin->uuid);
        $request->session()->forget('auth_user_id');

        return new JsonResponse([
            'success' => true,
            'id' => $superAdmin->uuid,
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
        ]);
    }
}
