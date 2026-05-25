<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\CancelOrder\CancelOrderCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
            'cancelled_by_user_id' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function toCommand(): CancelOrderCommand
    {
        return new CancelOrderCommand(
            id: (string) $this->input('id'),
            cancelledByUserId: (string) $this->input('cancelled_by_user_id'),
        );
    }
}
