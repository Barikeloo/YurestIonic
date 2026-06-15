<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\StartClosingCashSession\StartClosingCashSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class StartClosingCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function toCommand(): StartClosingCashSessionCommand
    {
        return new StartClosingCashSessionCommand(
            cashSessionId: (string) $this->input('cash_session_id'),
        );
    }
}
