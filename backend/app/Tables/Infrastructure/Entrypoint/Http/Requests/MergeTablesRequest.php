<?php

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Tables\Application\MergeTables\MergeTablesCommand;
use Illuminate\Foundation\Http\FormRequest;

final class MergeTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_ids' => ['required', 'array', 'min:2'],
            'table_ids.*' => ['required', 'string', 'uuid'],
        ];
    }

    public function toCommand(): MergeTablesCommand
    {
        return new MergeTablesCommand(
            tableIds: array_values((array) $this->input('table_ids')),
        );
    }
}
