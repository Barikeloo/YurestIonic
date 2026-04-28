<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\AuthenticateUserByPin\AuthenticateUserByPin;
use App\User\Infrastructure\Services\QuickAccessRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoginByPinController
{
    public function __construct(
        private readonly AuthenticateUserByPin $authenticateUserByPin,
        private readonly QuickAccessRecorder $quickAccessRecorder,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_uuid' => ['required', 'string', 'uuid'],
            'pin' => ['required', 'string', 'size:4'],
            'device_id' => ['sometimes', 'string', 'max:100'],
            'restaurant_id' => ['sometimes', 'nullable', 'string', 'uuid'],
        ]);

        $response = ($this->authenticateUserByPin)(
            $validated['user_uuid'],
            $validated['pin'],
            $validated['restaurant_id'] ?? null,
        );

        if ($response->success) {
            $request->session()->regenerate();
            $request->session()->put('auth_user_id', $response->id);

            $deviceId = $validated['device_id'] ?? $request->header('X-Device-Id');
            if (is_string($deviceId) && $deviceId !== '') {
                $this->quickAccessRecorder->record($response->id, $deviceId);
            }
        }

        return new JsonResponse($response->toArray(), $response->statusCode);
    }
}
