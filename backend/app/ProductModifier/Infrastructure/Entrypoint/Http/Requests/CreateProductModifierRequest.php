<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http\Requests;

use App\ProductModifier\Application\CreateProductModifier\CreateProductModifierCommand;
use App\ProductModifier\Domain\ValueObject\ModifierType;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateProductModifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(ModifierType::extra()->value(), ModifierType::accompaniment()->value())],
            'is_required' => ['sometimes', 'boolean'],
            'selection_type' => ['sometimes', 'string', Rule::in(ModifierSelectionType::single()->value(), ModifierSelectionType::multi()->value())],
            'price' => ['required', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function toCommand(string $productId): CreateProductModifierCommand
    {
        return new CreateProductModifierCommand(
            productId: $productId,
            name: (string) $this->input('name'),
            type: (string) $this->input('type'),
            isRequired: (bool) ($this->input('is_required') ?? false),
            selectionType: (string) ($this->input('selection_type') ?? 'multi'),
            price: (int) $this->input('price'),
            active: (bool) ($this->input('active') ?? true),
            sortOrder: (int) ($this->input('sort_order') ?? 0),
        );
    }
}
