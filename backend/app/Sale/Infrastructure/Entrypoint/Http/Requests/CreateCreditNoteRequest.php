<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\CreateCreditNote\CreateCreditNoteCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CreateCreditNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'uuid'],
            'parent_sale_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'total_cents' => ['required', 'integer', 'min:1'],
            'customer_fiscal_data' => ['sometimes', 'array', 'nullable'],
        ];
    }

    public function toCommand(string $restaurantId): CreateCreditNoteCommand
    {
        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new CreateCreditNoteCommand(
            restaurantId: $restaurantId,
            orderId: (string) $this->input('order_id'),
            parentSaleId: (string) $this->input('parent_sale_id'),
            openedByUserId: (string) $this->input('opened_by_user_id'),
            totalCents: (int) $this->input('total_cents'),
            customerFiscalData: $this->input('customer_fiscal_data'),
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
