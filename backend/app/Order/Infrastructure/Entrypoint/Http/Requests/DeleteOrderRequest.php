<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\DeleteOrder\DeleteOrderCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function toCommand(): DeleteOrderCommand
    {
        return new DeleteOrderCommand(
            id: (string) $this->input('id'),
        );
    }
}
