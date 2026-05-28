<?php

namespace App\Zone\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Zone\Application\UpdateZone\UpdateZoneCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateZoneRequest extends FormRequest
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
                Rule::unique('zones', 'name')
                    ->ignore($id, 'uuid')
                    ->where('restaurant_id', $tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function toCommand(string $id): UpdateZoneCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new UpdateZoneCommand(
            id: $id,
            name: (string) $this->input('name'),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
