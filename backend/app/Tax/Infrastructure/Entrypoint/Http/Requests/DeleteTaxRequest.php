<?php

namespace App\Tax\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tax\Application\DeleteTax\DeleteTaxCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): DeleteTaxCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new DeleteTaxCommand(
            id: $id,
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
