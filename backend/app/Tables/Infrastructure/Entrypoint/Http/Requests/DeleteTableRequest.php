<?php

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Tables\Application\DeleteTable\DeleteTableCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): DeleteTableCommand
    {
        return new DeleteTableCommand(id: $id);
    }
}
