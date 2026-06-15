<?php

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\SetProductActive\SetProductActiveCommand;
use Illuminate\Foundation\Http\FormRequest;

final class SetProductActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id, bool $active): SetProductActiveCommand
    {
        return new SetProductActiveCommand(id: $id, active: $active);
    }
}
