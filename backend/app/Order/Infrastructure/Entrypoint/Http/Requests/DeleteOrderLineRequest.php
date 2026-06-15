<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\DeleteOrderLine\DeleteOrderLineCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteOrderLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lineId' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lineId' => $this->route('lineId'),
        ]);
    }

    public function toCommand(): DeleteOrderLineCommand
    {
        $userId = $this->session()->get('auth_user_id');
        if (! is_string($userId) || $userId === '') {
            throw new \RuntimeException('Authenticated user is required.');
        }

        return new DeleteOrderLineCommand(
            lineId: (string) $this->input('lineId'),
            userId: $userId,
        );
    }
}
