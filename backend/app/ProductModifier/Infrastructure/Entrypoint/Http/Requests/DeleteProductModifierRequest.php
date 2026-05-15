<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http\Requests;

use App\ProductModifier\Application\DeleteProductModifier\DeleteProductModifierCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteProductModifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $modifierId): DeleteProductModifierCommand
    {
        return new DeleteProductModifierCommand(
            id: $modifierId,
        );
    }
}
