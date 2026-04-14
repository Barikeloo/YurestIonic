<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\AuthenticateForDeviceLink\AuthenticateForDeviceLink;
use App\User\Infrastructure\Services\QuickAccessRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoginForDeviceLinkController
{
    public function __construct(
        private AuthenticateForDeviceLink $authenticateForDeviceLink,
        private QuickAccessRecorder $quickAccessRecorder,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $response = ($this->authenticateForDeviceLink)(
            $validated['email'],
            $validated['password'],
        );

        if ($response->success) {
            $request->session()->regenerate();
            $request->session()->put('auth_user_id', $response->id);

            $deviceId = $request->input('device_id', $request->header('X-Device-Id'));
            if (is_string($deviceId) && $deviceId !== '') {
                $this->quickAccessRecorder->record($response->id, $deviceId);
            }
        }

        return new JsonResponse($response->toArray(), $response->statusCode);
    }
}
