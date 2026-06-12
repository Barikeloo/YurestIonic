<?php

namespace App\Family\Infrastructure\Entrypoint\Http\Requests;

use App\Family\Application\DeleteFamily\DeleteFamilyCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): DeleteFamilyCommand
    {
        return new DeleteFamilyCommand(id: $id);
    }
}
