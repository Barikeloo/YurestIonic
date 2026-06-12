<?php

namespace App\Tax\Infrastructure\Entrypoint\Http\Requests;

use App\Tax\Application\DeleteTax\DeleteTaxCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): DeleteTaxCommand
    {
        return new DeleteTaxCommand(id: $id);
    }
}
