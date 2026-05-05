<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\UpdateRestaurantUser\UpdateRestaurantUserCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRestaurantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:operator,supervisor,admin'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ];
    }

    public function toCommand(string $restaurantUuid, string $userUuid, ?string $actorUserUuid): UpdateRestaurantUserCommand
    {
        return new UpdateRestaurantUserCommand(
            restaurantUuid: $restaurantUuid,
            userUuid: $userUuid,
            name: $this->input('name'),
            email: $this->input('email'),
            plainPassword: $this->input('password'),
            role: $this->input('role'),
            plainPin: $this->input('pin'),
            actorUserUuid: $actorUserUuid,
        );
    }
}
