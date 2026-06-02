<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http\Requests;

use App\ProductModifier\Application\CreateProductModifier\CreateProductModifierCommand;
use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use App\ProductModifier\Domain\ValueObject\ModifierType;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateProductModifierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(ModifierType::extra()->value(), ModifierType::accompaniment()->value())],
            'is_required' => ['sometimes', 'boolean'],
            'selection_type' => ['sometimes', 'string', Rule::in(ModifierSelectionType::single()->value(), ModifierSelectionType::multi()->value())],
            'price' => ['required', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->input('type') === ModifierType::extra()->value() && (bool) $this->input('is_required')) {
                $v->errors()->add('is_required', 'Un extra no puede ser obligatorio.');
            }
        });
    }

    public function toCommand(string $productId): CreateProductModifierCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new CreateProductModifierCommand(
            productId: $productId,
            name: (string) $this->input('name'),
            type: (string) $this->input('type'),
            isRequired: (bool) ($this->input('is_required') ?? false),
            selectionType: (string) ($this->input('selection_type') ?? 'multi'),
            price: (int) $this->input('price'),
            active: (bool) ($this->input('active') ?? true),
            sortOrder: (int) ($this->input('sort_order') ?? 0),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
