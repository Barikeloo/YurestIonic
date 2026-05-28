<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\TransferOrder\TransferOrderCommand;
use Illuminate\Foundation\Http\FormRequest;

final class TransferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_table_id' => ['required', 'string', 'uuid'],
            'transferred_by_user_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function toCommand(): TransferOrderCommand
    {
        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new TransferOrderCommand(
            orderId: (string) $this->route('id'),
            toTableId: (string) $this->input('to_table_id'),
            transferredByUserId: (string) $this->input('transferred_by_user_id'),
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
