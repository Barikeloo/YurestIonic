<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\DeleteOrder\DeleteOrderCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function toCommand(): DeleteOrderCommand
    {
        return new DeleteOrderCommand(
            id: (string) $this->input('id'),
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
