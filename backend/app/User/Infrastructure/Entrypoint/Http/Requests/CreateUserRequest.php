<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\CreateUser\CreateUserCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email is already registered.',
        ];
    }

    public function toCommand(): CreateUserCommand
    {
        return new CreateUserCommand(
            name: (string) $this->input('name'),
            email: (string) $this->input('email'),
            plainPassword: (string) $this->input('password'),
        );
    }
}
