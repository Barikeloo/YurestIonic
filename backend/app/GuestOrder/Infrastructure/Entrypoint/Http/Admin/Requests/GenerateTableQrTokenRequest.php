<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Admin\Requests;

use App\GuestOrder\Application\GenerateTableQrToken\GenerateTableQrTokenCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class GenerateTableQrTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GenerateTableQrTokenCommand
    {
        $tenantContext = app(TenantContext::class);
        $restaurantId = $tenantContext->restaurantUuid();

        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        return new GenerateTableQrTokenCommand(
            tableId: (string) $this->route('tableId'),
            restaurantId: $restaurantId,
        );
    }
}
