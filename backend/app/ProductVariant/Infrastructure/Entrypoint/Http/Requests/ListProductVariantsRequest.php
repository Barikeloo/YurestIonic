<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http\Requests;

use App\ProductVariant\Application\ListProductVariants\ListProductVariantsCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListProductVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $productId): ListProductVariantsCommand
    {
        return new ListProductVariantsCommand(
            productId: $productId,
        );
    }
}
