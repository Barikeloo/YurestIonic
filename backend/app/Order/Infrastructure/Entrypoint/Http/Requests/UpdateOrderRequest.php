<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\UpdateOrder\UpdateOrderCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
            'diners' => ['sometimes', 'integer', 'min:1'],
            'action' => ['sometimes', 'string', 'in:mark-to-charge,close,cancel'],
            'closed_by_user_id' => ['sometimes', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function toCommand(): UpdateOrderCommand
    {
        return new UpdateOrderCommand(
            id: (string) $this->input('id'),
            diners: $this->input('diners') !== null ? (int) $this->input('diners') : null,
            action: $this->input('action') !== null ? (string) $this->input('action') : null,
            closedByUserId: $this->input('closed_by_user_id') !== null ? (string) $this->input('closed_by_user_id') : null,
        );
    }
}
