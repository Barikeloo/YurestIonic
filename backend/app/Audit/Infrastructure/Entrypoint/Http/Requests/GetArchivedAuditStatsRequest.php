<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http\Requests;

use App\Audit\Application\GetArchivedAuditStats\GetArchivedAuditStatsCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class GetArchivedAuditStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetArchivedAuditStatsCommand
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);

        return new GetArchivedAuditStatsCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
        );
    }
}
