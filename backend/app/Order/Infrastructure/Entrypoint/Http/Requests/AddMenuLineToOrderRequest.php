<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\AddMenuLineToOrder\AddMenuLineToOrderCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class AddMenuLineToOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'uuid'],
            'menu_id' => ['required', 'string', 'uuid'],
            'diner_number' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'device_id' => ['nullable', 'string'],
            'selections' => ['required', 'array', 'min:1'],
            'selections.*.section_id' => ['required', 'string', 'uuid'],
            'selections.*.product_id' => ['required', 'string', 'uuid'],
            'selections.*.variant_id' => ['nullable', 'string', 'uuid'],
            'selections.*.modifiers' => ['nullable', 'array'],
            'selections.*.modifiers.*.id' => ['required_with:selections.*.modifiers', 'string'],
            'selections.*.modifiers.*.name' => ['required_with:selections.*.modifiers', 'string'],
            'selections.*.modifiers.*.price' => ['required_with:selections.*.modifiers', 'integer', 'min:0'],
            'selections.*.modifiers.*.type' => ['required_with:selections.*.modifiers', 'string', 'in:extra,accompaniment'],
        ];
    }

    public function toCommand(): AddMenuLineToOrderCommand
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

        /** @var array<int, array{section_id: string, product_id: string, variant_id: ?string, modifiers: ?array<int, array{id: string, name: string, price: int, type: string}>}> $rawSelections */
        $rawSelections = $this->input('selections', []);

        $selections = array_map(static function (array $sel): array {
            return [
                'section_id' => (string) $sel['section_id'],
                'product_id' => (string) $sel['product_id'],
                'variant_id' => isset($sel['variant_id']) && $sel['variant_id'] !== '' ? (string) $sel['variant_id'] : null,
                'modifiers' => $sel['modifiers'] ?? [],
            ];
        }, $rawSelections);

        return new AddMenuLineToOrderCommand(
            restaurantId: $restaurantId,
            orderId: (string) $this->input('order_id'),
            menuId: (string) $this->input('menu_id'),
            userId: $userId,
            dinerNumber: $this->input('diner_number') !== null ? (int) $this->input('diner_number') : null,
            selections: $selections,
            notes: $this->input('notes') !== null ? (string) $this->input('notes') : null,
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
