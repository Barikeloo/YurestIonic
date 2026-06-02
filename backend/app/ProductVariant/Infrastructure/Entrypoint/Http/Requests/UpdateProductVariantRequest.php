<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http\Requests;

use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariantCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(string $variantId): UpdateProductVariantCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new UpdateProductVariantCommand(
            id: $variantId,
            name: (string) $this->input('name'),
            price: (int) $this->input('price'),
            stock: (int) $this->input('stock'),
            active: (bool) $this->input('active'),
            sortOrder: (int) ($this->input('sort_order') ?? 0),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
