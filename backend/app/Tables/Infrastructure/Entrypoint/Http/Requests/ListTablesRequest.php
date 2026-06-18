<?php

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Tables\Application\ListTables\ListTablesCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'all'     => ['nullable', 'boolean'],
            'zone_id' => ['nullable', 'uuid'],
        ];
    }

    public function toCommand(): ListTablesCommand
    {
        return new ListTablesCommand(
            includeDeleted: $this->query('all') === 'true',
            zoneId:         $this->query('zone_id') ?: null,
        );
    }
}
