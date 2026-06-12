<?php

namespace App\Tax\Infrastructure\Entrypoint\Http\Requests;

use App\Tax\Application\UpdateTax\UpdateTaxCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (string) $this->route('id');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('taxes', 'name')->ignore($id, 'uuid')->whereNull('deleted_at'),
            ],
            'percentage' => ['sometimes', 'integer', 'between:0,100'],
        ];
    }

    public function toCommand(string $id): UpdateTaxCommand
    {
        return new UpdateTaxCommand(
            id: $id,
            name: $this->input('name'),
            percentage: $this->has('percentage') ? (int) $this->input('percentage') : null,
        );
    }
}
