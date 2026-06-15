<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\BatchAddLinesToOrder\BatchAddLinesToOrderCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class BatchAddLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'uuid'],
            'product_lines' => ['nullable', 'array'],
            'product_lines.*.product_id' => ['required', 'string', 'uuid'],
            'product_lines.*.quantity' => ['required', 'integer', 'min:1'],
            'product_lines.*.variant_id' => ['nullable', 'string', 'uuid'],
            'product_lines.*.modifiers' => ['nullable', 'array'],
            'product_lines.*.modifiers.*.id' => ['required', 'string'],
            'product_lines.*.modifiers.*.name' => ['required', 'string'],
            'product_lines.*.modifiers.*.price' => ['required', 'integer'],
            'product_lines.*.modifiers.*.type' => ['required', 'string', 'in:extra,accompaniment'],
            'product_lines.*.diner_number' => ['nullable', 'integer', 'min:1'],
            'menu_lines' => ['nullable', 'array'],
            'menu_lines.*.menu_id' => ['required', 'string', 'uuid'],
            'menu_lines.*.notes' => ['nullable', 'string'],
            'menu_lines.*.selections' => ['required', 'array'],
            'menu_lines.*.selections.*.section_id' => ['required', 'string', 'uuid'],
            'menu_lines.*.selections.*.product_id' => ['required', 'string', 'uuid'],
            'menu_lines.*.selections.*.variant_id' => ['nullable', 'string', 'uuid'],
            'menu_lines.*.selections.*.modifiers' => ['nullable', 'array'],
            'menu_lines.*.selections.*.modifiers.*.id' => ['required', 'string'],
            'menu_lines.*.selections.*.modifiers.*.name' => ['required', 'string'],
            'menu_lines.*.selections.*.modifiers.*.price' => ['required', 'integer'],
            'menu_lines.*.selections.*.modifiers.*.type' => ['required', 'string', 'in:extra,accompaniment'],
        ];
    }

    public function toCommand(): BatchAddLinesToOrderCommand
    {
        $tenantContext = app(TenantContext::class);

        return new BatchAddLinesToOrderCommand(
            restaurantId: (string) $tenantContext->restaurantUuid(),
            orderId: (string) $this->input('order_id'),
            userId: (string) ($this->session()->get('auth_user_id') ?? ''),
            productLines: (array) ($this->input('product_lines') ?? []),
            menuLines: (array) ($this->input('menu_lines') ?? []),
        );
    }
}
