<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\AuthenticateUserByPin\AuthenticateUserByPinCommand;
use Illuminate\Foundation\Http\FormRequest;

final class LoginByPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_uuid' => ['required', 'string', 'uuid'],
            'pin' => ['required', 'string', 'size:4'],
            'device_id' => ['sometimes', 'string', 'max:100'],
            'restaurant_id' => ['sometimes', 'nullable', 'string', 'uuid'],
        ];
    }

    public function toCommand(): AuthenticateUserByPinCommand
    {
        $deviceId = $this->input('device_id');
        if ($deviceId === null) {
            $deviceId = $this->header('X-Device-Id');
        }

        $restaurantUuid = $this->input('restaurant_id');

        return new AuthenticateUserByPinCommand(
            userUuid: (string) $this->input('user_uuid'),
            pin: (string) $this->input('pin'),
            restaurantUuid: is_string($restaurantUuid) ? $restaurantUuid : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
        );
    }
}
