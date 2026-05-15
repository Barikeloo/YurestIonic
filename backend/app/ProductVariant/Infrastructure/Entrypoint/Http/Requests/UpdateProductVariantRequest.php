<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http\Requests;

use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariantCommand;
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
        ];
    }

    public function toCommand(string $variantId): UpdateProductVariantCommand
    {
        return new UpdateProductVariantCommand(
            id: $variantId,
            name: (string) $this->input('name'),
            price: (int) $this->input('price'),
            stock: (int) $this->input('stock'),
            active: (bool) $this->input('active'),
            sortOrder: (int) ($this->input('sort_order') ?? 0),
        );
    }
}
