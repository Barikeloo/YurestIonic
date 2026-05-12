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
        ];
    }

    public function toCommand(): UpdateChargeSessionDinersCommand
    {
        return new UpdateChargeSessionDinersCommand(
            chargeSessionId: (string) $this->route('id'),
            newDinersCount: (int) $this->input('diners_count'),
        );
    }
}
