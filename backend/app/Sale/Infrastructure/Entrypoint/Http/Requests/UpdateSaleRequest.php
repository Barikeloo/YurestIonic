<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\UpdateSale\UpdateSaleCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closed_by_user_id' => ['required', 'string', 'uuid'],
            'ticket_number' => ['required', 'integer', 'min:1'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): UpdateSaleCommand
    {
        return new UpdateSaleCommand(
            id: (string) $this->route('id'),
            closedByUserId: (string) $this->input('closed_by_user_id'),
            ticketNumber: (int) $this->input('ticket_number'),
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
