<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\ForceCloseCashSession\ForceCloseCashSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ForceCloseCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function toCommand(): ForceCloseCashSessionCommand
    {
        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new ForceCloseCashSessionCommand(
            cashSessionId: (string) $this->input('cash_session_id'),
            closedByUserId: (string) $this->input('closed_by_user_id'),
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
