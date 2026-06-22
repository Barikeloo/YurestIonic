<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\SavePendingLines\SavePendingLinesCommand;
use App\GuestOrder\Domain\ValueObject\GuestLineInput;
use Illuminate\Foundation\Http\FormRequest;

final class SavePendingLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lines'                          => ['required', 'array', 'min:1'],
            'lines.*.product_id'             => ['nullable', 'string', 'uuid'],
            'lines.*.menu_id'                => ['nullable', 'string', 'uuid'],
            'lines.*.quantity'               => ['required', 'integer', 'min:1'],
            'lines.*.variant_id'             => ['nullable', 'string', 'uuid'],
            'lines.*.modifier_ids'           => ['nullable', 'array'],
            'lines.*.modifier_ids.*'         => ['string', 'uuid'],
            'lines.*.notes'                  => ['nullable', 'string', 'max:255'],
            'lines.*.menu_selections'        => ['nullable', 'array'],
            'lines.*.menu_selections.*.section_id' => ['nullable', 'string'],
            'lines.*.menu_selections.*.product_id' => ['nullable', 'string', 'uuid'],
            'lines.*.menu_selections.*.variant_id' => ['nullable', 'string', 'uuid'],
        ];
    }

    public function toCommand(): SavePendingLinesCommand
    {
        $lines = array_map(
            fn (array $l): GuestLineInput => new GuestLineInput(
                productId: $l['product_id'] ?? null,
                menuId: $l['menu_id'] ?? null,
                quantity: (int) $l['quantity'],
                variantId: $l['variant_id'] ?? null,
                modifierIds: $l['modifier_ids'] ?? [],
                notes: $l['notes'] ?? null,
                menuSelections: $l['menu_selections'] ?? [],
            ),
            (array) $this->input('lines'),
        );

        return new SavePendingLinesCommand(
            token: (string) $this->route('token'),
            sessionToken: (string) $this->header('X-Guest-Session', ''),
            lines: $lines,
        );
    }
}
