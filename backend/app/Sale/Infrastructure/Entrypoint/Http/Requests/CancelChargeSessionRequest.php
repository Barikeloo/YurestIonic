<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\CancelChargeSession\CancelChargeSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CancelChargeSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancelled_by_user_id' => ['required', 'string', 'uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): CancelChargeSessionCommand
    {
        return new CancelChargeSessionCommand(
            chargeSessionId: (string) $this->route('id'),
            cancelledByUserId: (string) $this->input('cancelled_by_user_id'),
            reason: $this->input('reason') ? (string) $this->input('reason') : null,
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
