<?php

namespace App\Zone\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Zone\Application\CreateZone\CreateZoneCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateZoneRequest extends FormRequest
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
                Rule::unique('zones', 'name')
                    ->where('restaurant_id', $tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function toCommand(): CreateZoneCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new CreateZoneCommand(
            name: (string) $this->input('name'),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
