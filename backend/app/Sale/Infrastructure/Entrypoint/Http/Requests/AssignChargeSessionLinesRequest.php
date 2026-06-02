<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\AssignChargeSessionLines\AssignChargeSessionLinesCommand;
use Illuminate\Foundation\Http\FormRequest;

final class AssignChargeSessionLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignments' => ['present', 'array'],
            'assignments.*.order_line_id' => ['required', 'string', 'uuid'],
            'assignments.*.diner_number' => ['required', 'integer', 'min:1'],
            'device_id' => ['nullable', 'string'],
        ];
    }

    public function toCommand(): AssignChargeSessionLinesCommand
    {
        $items = $this->input('assignments', []);

        $assignments = array_map(static fn (array $item): array => [
            'order_line_id' => (string) $item['order_line_id'],
            'diner_number' => (int) $item['diner_number'],
        ], $items);

        return new AssignChargeSessionLinesCommand(
            chargeSessionId: (string) $this->route('id'),
            assignments: $assignments,
            deviceId: $this->input('device_id'),
            ipAddress: $this->ip(),
        );
    }
}
