<?php

namespace App\Family\Infrastructure\Entrypoint\Http\Requests;

use App\Family\Application\CreateFamily\CreateFamilyCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantContext = app(TenantContext::class);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('families', 'name')
                    ->where('restaurant_id', $tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function toCommand(): CreateFamilyCommand
    {
        return new CreateFamilyCommand(
            name: (string) $this->input('name'),
        );
    }
}
