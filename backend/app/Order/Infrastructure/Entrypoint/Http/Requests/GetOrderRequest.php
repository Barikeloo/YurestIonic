<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\GetOrder\GetOrderCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetOrderRequest extends FormRequest
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

    public function toCommand(): GetOrderCommand
    {
        return new GetOrderCommand(
            id: (string) $this->input('id'),
        );
    }
}
