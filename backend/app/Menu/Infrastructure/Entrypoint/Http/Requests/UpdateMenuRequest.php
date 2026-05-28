<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http\Requests;

use App\Menu\Application\UpdateMenu\UpdateMenuCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantContext = app(TenantContext::class);
        $restaurantId = $tenantContext->requireRestaurantId();

        return [
            'tax_id' => [
                'required',
                'uuid',
                Rule::exists('taxes', 'uuid')
                    ->where('restaurant_id', $restaurantId)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'validity_from' => ['nullable', 'date_format:Y-m-d'],
            'validity_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:validity_from'],
            'available_days' => ['required', 'array', 'min:1'],
            'available_days.*' => ['integer', 'between:1,7'],
            'available_from_time' => ['nullable', 'date_format:H:i', 'required_with:available_to_time'],
            'available_to_time' => ['nullable', 'date_format:H:i', 'required_with:available_from_time', 'after:available_from_time'],
            'active' => ['required', 'boolean'],

            'sections' => ['required', 'array', 'min:1'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.position' => ['sometimes', 'integer', 'min:0'],
            'sections.*.min_choices' => ['required', 'integer', 'min:0'],
            'sections.*.max_choices' => ['required', 'integer', 'min:1'],
            'sections.*.items' => ['required', 'array', 'min:1'],
            'sections.*.items.*.product_id' => [
                'required',
                'uuid',
                Rule::exists('products', 'uuid')
                    ->where('restaurant_id', $restaurantId)
                    ->whereNull('deleted_at'),
            ],
            'sections.*.items.*.variant_id' => [
                'nullable',
                'uuid',
                Rule::exists('product_variants', 'uuid')
                    ->whereNull('deleted_at'),
            ],
            'sections.*.items.*.extra_price' => ['sometimes', 'integer', 'min:0'],
            'sections.*.items.*.position' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function toCommand(string $id): UpdateMenuCommand
    {
        $tenantContext = app(TenantContext::class);

        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        $userId = $this->session()->get('auth_user_id');

        return new UpdateMenuCommand(
            id: $id,
            taxId: (string) $this->input('tax_id'),
            name: (string) $this->input('name'),
            description: $this->input('description') !== null && $this->input('description') !== ''
                ? (string) $this->input('description')
                : null,
            price: (int) $this->input('price'),
            validityFrom: $this->input('validity_from') !== null && $this->input('validity_from') !== ''
                ? (string) $this->input('validity_from')
                : null,
            validityTo: $this->input('validity_to') !== null && $this->input('validity_to') !== ''
                ? (string) $this->input('validity_to')
                : null,
            availableDays: MenuRequestHelpers::weekdaysToBitmask((array) $this->input('available_days', [])),
            availableFromTime: $this->input('available_from_time') !== null && $this->input('available_from_time') !== ''
                ? (string) $this->input('available_from_time')
                : null,
            availableToTime: $this->input('available_to_time') !== null && $this->input('available_to_time') !== ''
                ? (string) $this->input('available_to_time')
                : null,
            active: (bool) $this->input('active'),
            sections: MenuRequestHelpers::buildSections((array) $this->input('sections', [])),
            restaurantId: (string) $tenantContext->restaurantUuid(),
            userId: is_string($userId) && $userId !== '' ? $userId : null,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
