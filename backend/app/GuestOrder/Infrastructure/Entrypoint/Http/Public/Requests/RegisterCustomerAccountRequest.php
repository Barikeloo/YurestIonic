<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\RegisterCustomerAccount\RegisterCustomerAccountCommand;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterCustomerAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    public function toCommand(): RegisterCustomerAccountCommand
    {
        return new RegisterCustomerAccountCommand(
            token: (string) $this->route('token'),
            name: (string) $this->input('name'),
            email: (string) $this->input('email'),
            password: (string) $this->input('password'),
        );
    }
}
