<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http\Requests;

use App\ProductModifier\Application\ListProductModifiers\ListProductModifiersCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListProductModifiersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $productId): ListProductModifiersCommand
    {
        return new ListProductModifiersCommand(
            productId: $productId,
        );
    }
}
