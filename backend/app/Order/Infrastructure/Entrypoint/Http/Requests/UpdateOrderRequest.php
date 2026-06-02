<?php

declare(strict_types=1);

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
            'device_id' => ['nullable', 'string'],
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
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
