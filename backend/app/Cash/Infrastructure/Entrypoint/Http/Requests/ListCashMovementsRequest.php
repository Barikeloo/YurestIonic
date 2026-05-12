<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\ListCashMovements\ListCashMovementsCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListCashMovementsRequest extends FormRequest
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

    public function toCommand(): ListCashMovementsCommand
    {
        return new ListCashMovementsCommand(
            cashSessionId: (string) $this->input('cash_session_id'),
        );
    }
}
