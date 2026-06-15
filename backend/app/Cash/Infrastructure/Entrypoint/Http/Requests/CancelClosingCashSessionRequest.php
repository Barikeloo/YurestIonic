<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\CancelClosingCashSession\CancelClosingCashSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CancelClosingCashSessionRequest extends FormRequest
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

    public function toCommand(): CancelClosingCashSessionCommand
    {
        return new CancelClosingCashSessionCommand(
            cashSessionId: (string) $this->input('cash_session_id'),
        );
    }
}
