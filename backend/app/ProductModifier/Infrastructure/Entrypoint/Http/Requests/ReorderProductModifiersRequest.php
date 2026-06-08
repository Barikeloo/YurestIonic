<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http\Requests;

use App\ProductModifier\Application\ReorderProductModifiers\ReorderProductModifiersCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ReorderProductModifiersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string', 'uuid'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function toCommand(string $productId): ReorderProductModifiersCommand
    {

        $items = (array) $this->input('items');

        return new ReorderProductModifiersCommand(
            productId: $productId,
            items: $items,
        );
    }
}
