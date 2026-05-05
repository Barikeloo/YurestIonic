<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\AuthenticateForDeviceLink\AuthenticateForDeviceLinkCommand;
use Illuminate\Foundation\Http\FormRequest;

final class LoginForDeviceLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): AuthenticateForDeviceLinkCommand
    {
        $deviceIdFromBody = $this->input('device_id');
        $deviceIdFromHeader = $this->header('X-Device-Id');

        $deviceId = is_string($deviceIdFromBody) && $deviceIdFromBody !== ''
            ? $deviceIdFromBody
            : (is_string($deviceIdFromHeader) && $deviceIdFromHeader !== '' ? $deviceIdFromHeader : null);

        return new AuthenticateForDeviceLinkCommand(
            email: (string) $this->input('email'),
            password: (string) $this->input('password'),
            deviceId: $deviceId,
        );
    }
}
