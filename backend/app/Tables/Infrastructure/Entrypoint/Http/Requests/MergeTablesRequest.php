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

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new MergeTablesCommand(
            tableIds: (array) $this->input('table_ids'),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
