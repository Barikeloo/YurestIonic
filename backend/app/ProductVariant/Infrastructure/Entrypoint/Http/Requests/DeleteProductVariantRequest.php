<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http\Requests;

use App\ProductVariant\Application\DeleteProductVariant\DeleteProductVariantCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(string $variantId): DeleteProductVariantCommand
    {
        return new DeleteProductVariantCommand(id: $variantId);
    }
}
