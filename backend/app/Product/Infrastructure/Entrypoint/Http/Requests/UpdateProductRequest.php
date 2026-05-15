<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\UpdateProduct\UpdateProductCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
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
            'active' => ['required', 'boolean'],
        ];
    }

    public function toCommand(string $id): UpdateProductCommand
    {
        return new UpdateProductCommand(
            id: $id,
            familyId: (string) $this->input('family_id'),
            taxId: (string) $this->input('tax_id'),
            imageSrc: $this->input('image_src') ? (string) $this->input('image_src') : null,
            name: (string) $this->input('name'),
            price: (int) $this->input('price'),
            stock: (int) $this->input('stock'),
            active: (bool) $this->input('active'),
        );
    }
}
