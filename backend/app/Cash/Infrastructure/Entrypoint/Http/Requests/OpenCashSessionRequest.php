<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\OpenCashSession\OpenCashSessionCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class OpenCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:100'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'initial_amount_cents' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): OpenCashSessionCommand
    {
        $restaurantUuid = app(TenantContext::class)->restaurantUuid()
            ?? throw new \RuntimeException('Tenant context is required.');

        return new OpenCashSessionCommand(
            restaurantId: $restaurantUuid,
            deviceId: (string) $this->input('device_id'),
            openedByUserId: (string) $this->input('opened_by_user_id'),
            initialAmountCents: (int) $this->input('initial_amount_cents'),
            notes: $this->input('notes'),
        );
    }
}
