<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Public\Requests;

use App\GuestOrder\Application\SubmitGuestRound\SubmitGuestRoundCommand;
use Illuminate\Foundation\Http\FormRequest;

final class SubmitGuestRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'line_ids'         => ['required', 'array', 'min:1'],
            'line_ids.*'       => ['required', 'string', 'uuid'],
            'idempotency_key'  => ['required', 'string', 'uuid'],
            'round_label'      => ['nullable', 'string', 'max:100'],
        ];
    }

    public function toCommand(): SubmitGuestRoundCommand
    {
        return new SubmitGuestRoundCommand(
            token: (string) $this->route('token'),
            sessionToken: (string) $this->header('X-Guest-Session', ''),
            lineIds: (array) $this->input('line_ids'),
            idempotencyKey: (string) $this->input('idempotency_key'),
            roundLabel: $this->input('round_label'),
        );
    }
}
