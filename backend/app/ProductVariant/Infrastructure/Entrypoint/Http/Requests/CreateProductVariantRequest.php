<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http\Requests;

use App\ProductVariant\Application\CreateProductVariant\CreateProductVariantCommand;
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
        ];
    }

    public function toCommand(string $productId): CreateProductVariantCommand
    {
        return new CreateProductVariantCommand(
            productId: $productId,
            name: (string) $this->input('name'),
            price: (int) $this->input('price'),
            stock: (int) $this->input('stock'),
            active: (bool) ($this->input('active') ?? true),
            sortOrder: (int) ($this->input('sort_order') ?? 0),
        );
    }
}
