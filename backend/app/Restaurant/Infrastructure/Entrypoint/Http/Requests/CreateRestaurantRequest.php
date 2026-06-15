<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http\Requests;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurantCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CreateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:50'],
            'company_mode' => ['sometimes', 'string', 'in:existing,new'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:restaurants,email'],
            'password' => ['required', 'string', 'min:8'],
            'pin' => ['sometimes', 'nullable', 'digits:4'],
        ];
    }

    public function toCommand(): CreateRestaurantCommand
    {
        return new CreateRestaurantCommand(
            name: (string) $this->input('name'),
            legalName: $this->input('legal_name'),
            taxId: (string) $this->input('tax_id'),
            email: (string) $this->input('email'),
            password: (string) $this->input('password'),
            pin: $this->input('pin'),
            companyMode: $this->input('company_mode') ?? 'new',
        );
    }
}
