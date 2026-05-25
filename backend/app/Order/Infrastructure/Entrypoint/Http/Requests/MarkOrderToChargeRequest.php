<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\MarkOrderToCharge\MarkOrderToChargeCommand;
use Illuminate\Foundation\Http\FormRequest;

final class MarkOrderToChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function toCommand(): MarkOrderToChargeCommand
    {
        return new MarkOrderToChargeCommand(
            id: (string) $this->input('id'),
            closedByUserId: (string) $this->input('closed_by_user_id'),
        );
    }
}
