<?php

namespace App\Tax\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
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
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new UpdateTaxCommand(
            id: $id,
            restaurantId: (string) $tenantContext->restaurantUuid(),
            name: $this->input('name'),
            percentage: $this->has('percentage') ? (int) $this->input('percentage') : null,
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
