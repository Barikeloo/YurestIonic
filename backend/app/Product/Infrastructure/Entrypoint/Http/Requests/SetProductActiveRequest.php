<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\SetProductActive\SetProductActiveCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class SetProductActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id, bool $active): SetProductActiveCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new SetProductActiveCommand(
            id: $id,
            active: $active,
            restaurantId: (string) $tenantContext->restaurantUuid(),
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
