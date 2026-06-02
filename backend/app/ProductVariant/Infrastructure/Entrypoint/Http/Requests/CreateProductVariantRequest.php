<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http\Requests;

use App\ProductVariant\Application\CreateProductVariant\CreateProductVariantCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class CreateProductVariantRequest extends FormRequest
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
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(string $productId): CreateProductVariantCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new CreateProductVariantCommand(
            productId: $productId,
            name: (string) $this->input('name'),
            price: (int) $this->input('price'),
            stock: (int) $this->input('stock'),
            active: (bool) ($this->input('active') ?? true),
            sortOrder: (int) ($this->input('sort_order') ?? 0),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
