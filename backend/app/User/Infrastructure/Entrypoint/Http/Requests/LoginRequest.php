<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\AuthenticateUser\AuthenticateUserCommand;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
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
            'device_id' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function toCommand(): AuthenticateUserCommand
    {
        $deviceId = $this->input('device_id');
        if ($deviceId === null) {
            $deviceId = $this->header('X-Device-Id');
        }

        return new AuthenticateUserCommand(
            email: (string) $this->input('email'),
            plainPassword: (string) $this->input('password'),
            deviceId: is_string($deviceId) ? $deviceId : null,
        );
    }
}
