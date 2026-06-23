<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Admin\Requests;

use App\GuestOrder\Application\GetTableQrToken\GetTableQrTokenCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class GetTableQrTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetTableQrTokenCommand
    {
        $tenantContext = app(TenantContext::class);
        $restaurantId  = $tenantContext->restaurantUuid();

        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        return new GetTableQrTokenCommand(
            tableId: (string) $this->route('tableId'),
            restaurantId: $restaurantId,
        );
    }
}
