<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\UpdateChargeSessionDiners\UpdateChargeSessionDinersCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateChargeSessionDinersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diners_count' => ['required', 'integer', 'min:1'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): UpdateChargeSessionDinersCommand
    {
        return new UpdateChargeSessionDinersCommand(
            chargeSessionId: (string) $this->route('id'),
            newDinersCount: (int) $this->input('diners_count'),
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
