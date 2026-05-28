<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\CreateProduct\CreateProductCommand;
use App\Product\Domain\ValueObject\ProductAllergens;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantContext = app(TenantContext::class);

        return [
            'family_id' => [
                'required',
                'uuid',
                Rule::exists('families', 'uuid')
                    ->where('restaurant_id', $tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
            'tax_id' => [
                'required',
                'uuid',
                Rule::exists('taxes', 'uuid')
                    ->where('restaurant_id', $tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
            'image_src' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'allergens' => ['sometimes', 'array'],
            'allergens.*' => ['string', Rule::in(ProductAllergens::ALLERGENS)],
        ];
    }

    public function toCommand(): CreateProductCommand
    {
        $allergens = $this->input('allergens', []);
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new CreateProductCommand(
            familyId: (string) $this->input('family_id'),
            taxId: (string) $this->input('tax_id'),
            imageSrc: $this->input('image_src') ? (string) $this->input('image_src') : null,
            name: (string) $this->input('name'),
            price: (int) $this->input('price'),
            stock: (int) $this->input('stock'),
            active: (bool) ($this->input('active') ?? true),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            allergens: is_array($allergens) ? array_values(array_map('strval', $allergens)) : [],
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
