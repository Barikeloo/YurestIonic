<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\CreateRestaurantUser\CreateRestaurantUserCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CreateRestaurantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:operator,supervisor,admin'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ];
    }

    public function toCommand(
        string $restaurantUuid,
        ?string $actorUserUuid,
        ?string $actorSuperAdminUuid,
    ): CreateRestaurantUserCommand {
        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new CreateRestaurantUserCommand(
            name: (string) $this->input('name'),
            email: (string) $this->input('email'),
            plainPassword: (string) $this->input('password'),
            restaurantUuid: $restaurantUuid,
            role: (string) ($this->input('role') ?? 'operator'),
            plainPin: $this->input('pin'),
            actorUserUuid: $actorUserUuid,
            actorSuperAdminUuid: $actorSuperAdminUuid,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
