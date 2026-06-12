<?php

namespace App\Tax\Infrastructure\Entrypoint\Http\Requests;

use App\Tax\Application\CreateTax\CreateTaxCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taxes', 'name')->whereNull('deleted_at'),
            ],
            'percentage' => ['required', 'integer', 'between:0,100'],
        ];
    }

    public function toCommand(): CreateTaxCommand
    {
        return new CreateTaxCommand(
            name: (string) $this->input('name'),
            percentage: (int) $this->input('percentage'),
        );
    }
}
