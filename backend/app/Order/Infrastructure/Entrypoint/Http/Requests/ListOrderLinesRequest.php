<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\ListOrderLines\ListOrderLinesCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListOrderLinesRequest extends FormRequest
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

    public function toCommand(): ListOrderLinesCommand
    {
        return new ListOrderLinesCommand(
            orderId: (string) $this->input('id'),
        );
    }
}
