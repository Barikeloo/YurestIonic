<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http\Requests;

use App\Audit\Application\GetAuditEvent\GetAuditEventCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class GetAuditEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'uuid' => $this->route('uuid'),
        ]);
    }

    public function toCommand(): GetAuditEventCommand
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);

        return new GetAuditEventCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
            uuid: (string) $this->input('uuid'),
        );
    }
}
