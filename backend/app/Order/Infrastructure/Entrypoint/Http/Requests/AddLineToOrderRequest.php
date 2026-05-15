<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\AddLineToOrder\AddLineToOrderCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class AddLineToOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'uuid'],
            'product_id' => ['required', 'string', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
            'diner_number' => ['nullable', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'string', 'uuid'],
            'modifiers' => ['nullable', 'array'],
            'modifiers.*.id' => ['required_with:modifiers', 'string'],
            'modifiers.*.name' => ['required_with:modifiers', 'string'],
            'modifiers.*.price' => ['required_with:modifiers', 'integer', 'min:0'],
            'modifiers.*.type' => ['required_with:modifiers', 'string', 'in:extra,accompaniment'],
        ];
    }

    public function toCommand(): AddLineToOrderCommand
    {
        $tenantContext = app(TenantContext::class);
        $restaurantId = $tenantContext->restaurantUuid();

        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        $userId = $this->session()->get('auth_user_id');
        if (! is_string($userId) || $userId === '') {
            throw new \RuntimeException('Authenticated user is required.');
        }

        return new AddLineToOrderCommand(
            restaurantId: $restaurantId,
            orderId: (string) $this->input('order_id'),
            productId: (string) $this->input('product_id'),
            userId: $userId,
            quantity: (int) $this->input('quantity'),
            dinerNumber: $this->input('diner_number') !== null ? (int) $this->input('diner_number') : null,
            variantId: $this->input('variant_id') !== null ? (string) $this->input('variant_id') : null,
            modifiers: $this->input('modifiers') !== null ? $this->input('modifiers') : null,
        );
    }
}
