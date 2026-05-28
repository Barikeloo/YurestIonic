<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\CancelSale\CancelSaleCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CancelSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'string', 'uuid'],
            'cancelled_by_user_id' => ['required', 'string', 'uuid'],
            'reason' => ['required', 'string'],
        ];
    }

    public function toCommand(): CancelSaleCommand
    {
        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new CancelSaleCommand(
            saleId: (string) $this->input('sale_id'),
            cancelledByUserId: (string) $this->input('cancelled_by_user_id'),
            reason: (string) $this->input('reason'),
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
