<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http\Requests;

use App\Audit\Application\ListAuditEvents\ListAuditEventsCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class ListAuditEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'in:order,caja,sale,table,catalog,auth,config,system'],
            'severity' => ['nullable', 'string', 'in:info,warning,danger,critical,success'],
            'user_id' => ['nullable', 'string', 'uuid'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'min:2', 'max:200'],
            'anomaly_only' => ['nullable', 'boolean'],
            'include_archived' => ['nullable', 'boolean'],
            'cursor' => ['nullable', 'string', 'max:500'],
            'since' => ['nullable', 'string', 'uuid'],
        ];
    }

    public function toCommand(): ListAuditEventsCommand
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);

        return new ListAuditEventsCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
            category: $this->input('category'),
            severity: $this->input('severity'),
            userId: $this->input('user_id'),
            deviceId: $this->input('device_id'),
            dateFrom: $this->input('date_from'),
            dateTo: $this->input('date_to'),
            search: $this->input('q'),
            anomalyOnly: $this->boolean('anomaly_only'),
            includeArchived: $this->boolean('include_archived'),
            cursor: $this->input('cursor'),
            sinceUuid: $this->input('since'),
        );
    }
}
