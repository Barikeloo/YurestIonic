<?php

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
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
        $tenantContext = app(TenantContext::class);
        $restaurantId = $tenantContext->restaurantUuid();

        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        return new MergeTablesCommand(
            tableIds: array_values((array) $this->input('table_ids')),
            restaurantId: $restaurantId,
        );
    }
}
