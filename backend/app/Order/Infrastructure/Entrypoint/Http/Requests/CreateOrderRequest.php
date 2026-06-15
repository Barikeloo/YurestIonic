<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\CreateOrder\CreateOrderCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'table_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'diners' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toCommand(): CreateOrderCommand
    {
        $tenantContext = app(TenantContext::class);
        $restaurantId = $tenantContext->restaurantUuid();

        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        return new CreateOrderCommand(
            restaurantId: $restaurantId,
            tableId: (string) $this->input('table_id'),
            openedByUserId: (string) $this->input('opened_by_user_id'),
            diners: (int) $this->input('diners'),
        );
    }
}
