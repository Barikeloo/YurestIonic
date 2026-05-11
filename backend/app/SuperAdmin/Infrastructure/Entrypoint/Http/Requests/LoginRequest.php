<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http\Requests;

use App\SuperAdmin\Application\AuthenticateSuperAdmin\AuthenticateSuperAdminCommand;
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
        ];
    }

    public function toCommand(): AuthenticateSuperAdminCommand
    {
        return new AuthenticateSuperAdminCommand(
            email: (string) $this->input('email'),
            plainPassword: (string) $this->input('password'),
        );
    }
}
