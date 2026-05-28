<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\CloseCashSession\CloseCashSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CloseCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
            'final_amount_cents' => ['required', 'integer', 'min:0'],
            'discrepancy_reason' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): CloseCashSessionCommand
    {
        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new CloseCashSessionCommand(
            cashSessionId: (string) $this->input('cash_session_id'),
            closedByUserId: (string) $this->input('closed_by_user_id'),
            finalAmountCents: (int) $this->input('final_amount_cents'),
            discrepancyReason: $this->input('discrepancy_reason'),
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
