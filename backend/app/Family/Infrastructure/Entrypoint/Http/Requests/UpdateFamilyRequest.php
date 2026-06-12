<?php

namespace App\Family\Infrastructure\Entrypoint\Http\Requests;

use App\Family\Application\UpdateFamily\UpdateFamilyCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantContext = app(TenantContext::class);
        $id = (string) $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('families', 'name')
                    ->ignore($id, 'uuid')
                    ->where('restaurant_id', $tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function toCommand(string $id): UpdateFamilyCommand
    {
        return new UpdateFamilyCommand(
            id: $id,
            name: (string) $this->input('name'),
        );
    }
}
