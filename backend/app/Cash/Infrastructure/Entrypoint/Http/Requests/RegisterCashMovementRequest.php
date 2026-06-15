<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\RegisterCashMovement\RegisterCashMovementCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'string', 'uuid'],
            'type' => ['required', 'string', 'in:in,out'],
            'reason_code' => ['required', 'string', 'in:change_refill,supplier_payment,tip_declared,sangria,adjustment,other'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'string', 'uuid'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): RegisterCashMovementCommand
    {
        $restaurantUuid = app(TenantContext::class)->restaurantUuid()
            ?? throw new \RuntimeException('Tenant context is required.');

        return new RegisterCashMovementCommand(
            restaurantId: $restaurantUuid,
            cashSessionId: (string) $this->input('cash_session_id'),
            type: (string) $this->input('type'),
            reasonCode: (string) $this->input('reason_code'),
            amountCents: (int) $this->input('amount_cents'),
            userId: (string) $this->input('user_id'),
            description: $this->input('description'),
        );
    }
}
