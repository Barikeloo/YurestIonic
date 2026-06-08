<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests;

use App\AuditSavedView\Application\ListAuditSavedViews\ListAuditSavedViewsCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class ListAuditSavedViewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): ListAuditSavedViewsCommand
    {

        $tenantContext = app(TenantContext::class);

        return new ListAuditSavedViewsCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: (string) ($this->session()->get('auth_user_id') ?? ''),
        );
    }
}
