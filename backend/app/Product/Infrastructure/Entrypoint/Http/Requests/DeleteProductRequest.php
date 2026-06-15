<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\DeleteProduct\DeleteProductCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): DeleteProductCommand
    {
        return new DeleteProductCommand(id: $id);
    }
}
