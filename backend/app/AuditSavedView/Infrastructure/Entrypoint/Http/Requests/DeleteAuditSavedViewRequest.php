<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests;

use App\AuditSavedView\Application\DeleteAuditSavedView\DeleteAuditSavedViewCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteAuditSavedViewRequest extends FormRequest
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

    public function toCommand(): DeleteAuditSavedViewCommand
    {

        $tenantContext = app(TenantContext::class);

        return new DeleteAuditSavedViewCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: (string) ($this->session()->get('auth_user_id') ?? ''),
            uuid: (string) $this->input('uuid'),
        );
    }
}
