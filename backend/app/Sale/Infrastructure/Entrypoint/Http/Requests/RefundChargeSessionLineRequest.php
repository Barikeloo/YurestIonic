<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\RefundChargeSessionLine\RefundChargeSessionLineCommand;
use Illuminate\Foundation\Http\FormRequest;

final class RefundChargeSessionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_line_id' => ['required', 'string', 'uuid'],
            'refunded_by_user_id' => ['required', 'string', 'uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): RefundChargeSessionLineCommand
    {
        $reason = $this->input('reason');

        return new RefundChargeSessionLineCommand(
            chargeSessionId: (string) $this->route('id'),
            orderLineId: (string) $this->input('order_line_id'),
            refundedByUserId: (string) $this->input('refunded_by_user_id'),
            reason: is_string($reason) && $reason !== '' ? $reason : null,
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
