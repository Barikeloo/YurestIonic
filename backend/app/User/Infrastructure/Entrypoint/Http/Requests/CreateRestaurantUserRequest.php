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

    public function toCommand(string $restaurantUuid): CreateRestaurantUserCommand
    {
        return new CreateRestaurantUserCommand(
            name: (string) $this->input('name'),
            email: (string) $this->input('email'),
            plainPassword: (string) $this->input('password'),
            restaurantUuid: $restaurantUuid,
            role: (string) ($this->input('role') ?? 'operator'),
            plainPin: $this->input('pin'),
        );
    }
}
