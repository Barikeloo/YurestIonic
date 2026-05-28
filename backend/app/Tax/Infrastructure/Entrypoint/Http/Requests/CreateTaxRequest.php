<?php

namespace App\Tax\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
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
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new CreateTaxCommand(
            name: (string) $this->input('name'),
            percentage: (int) $this->input('percentage'),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
