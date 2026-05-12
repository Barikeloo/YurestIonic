<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\GenerateZReport\GenerateZReportCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GenerateZReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'string', 'uuid'],
            'final_amount_cents' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toCommand(): GenerateZReportCommand
    {
        return new GenerateZReportCommand(
            cashSessionId: (string) $this->input('cash_session_id'),
            finalAmountCents: $this->input('final_amount_cents') !== null
                ? (int) $this->input('final_amount_cents')
                : null,
        );
    }
}
