<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests;

use App\AuditSavedView\Application\CreateAuditSavedView\CreateAuditSavedViewCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class CreateAuditSavedViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:40'],
            'filters' => ['required', 'array'],
        ];
    }

    public function toCommand(): CreateAuditSavedViewCommand
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);

        return new CreateAuditSavedViewCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: (string) ($this->session()->get('auth_user_id') ?? ''),
            name: (string) $this->input('name'),
            icon: $this->input('icon') !== null ? (string) $this->input('icon') : null,
            filters: (array) $this->input('filters'),
        );
    }
}
