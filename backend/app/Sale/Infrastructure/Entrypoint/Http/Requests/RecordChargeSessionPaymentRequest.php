<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\RecordChargeSessionPayment\RecordChargeSessionPaymentCommand;
use Illuminate\Foundation\Http\FormRequest;

final class RecordChargeSessionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diner_number' => ['nullable', 'integer', 'min:1'],
            'amount_cents' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,card,bizum,voucher,invitation,other'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
            'device_id' => ['required', 'string'],
        ];
    }

    public function toCommand(): RecordChargeSessionPaymentCommand
    {
        return new RecordChargeSessionPaymentCommand(
            chargeSessionId: (string) $this->route('id'),
            paymentMethod: (string) $this->input('payment_method'),
            openedByUserId: (string) $this->input('opened_by_user_id'),
            closedByUserId: (string) $this->input('closed_by_user_id'),
            deviceId: (string) $this->input('device_id'),
            dinerNumber: $this->input('diner_number') ? (int) $this->input('diner_number') : null,
            amountCents: $this->input('amount_cents') ? (int) $this->input('amount_cents') : null,
        );
    }
}
