<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\LoginCustomerAccount\LoginCustomerAccountCommand;
use Illuminate\Foundation\Http\FormRequest;

final class LoginCustomerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function toCommand(): LoginCustomerAccountCommand
    {
        return new LoginCustomerAccountCommand(
            token: (string) $this->route('token'),
            email: (string) $this->input('email'),
            password: (string) $this->input('password'),
        );
    }
}
