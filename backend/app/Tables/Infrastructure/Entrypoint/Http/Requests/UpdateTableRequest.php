<?php

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tables\Application\UpdateTable\UpdateTableCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTableRequest extends FormRequest
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

    public function toCommand(string $id): UpdateTableCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new UpdateTableCommand(
            id: $id,
            zoneId: (string) $this->input('zone_id'),
            name: (string) $this->input('name'),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
