<?php

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tables\Application\CreateTable\CreateTableCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantContext = app(TenantContext::class);

        return [
            'zone_id' => [
                'required',
                'uuid',
                Rule::exists('zones', 'uuid')
                    ->where('restaurant_id', $tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function toCommand(): CreateTableCommand
    {
        return new CreateTableCommand(
            zoneId: (string) $this->input('zone_id'),
            name: (string) $this->input('name'),
        );
    }
}
