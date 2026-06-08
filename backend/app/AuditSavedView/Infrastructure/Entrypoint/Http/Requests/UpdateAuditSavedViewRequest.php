<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests;

use App\AuditSavedView\Application\UpdateAuditSavedView\UpdateAuditSavedViewCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAuditSavedViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => ['required', 'string', 'uuid'],
            'name' => ['nullable', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:40'],
            'filters' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'uuid' => $this->route('uuid'),
        ]);
    }

    public function toCommand(): UpdateAuditSavedViewCommand
    {

        $tenantContext = app(TenantContext::class);

        return new UpdateAuditSavedViewCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: (string) ($this->session()->get('auth_user_id') ?? ''),
            uuid: (string) $this->input('uuid'),
            name: $this->input('name') !== null ? (string) $this->input('name') : null,
            icon: $this->input('icon') !== null ? (string) $this->input('icon') : null,
            filters: $this->input('filters') !== null ? (array) $this->input('filters') : null,
        );
    }
}
