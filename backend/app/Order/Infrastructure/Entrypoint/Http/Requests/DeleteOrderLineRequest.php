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
        return new DeleteOrderLineCommand(
            lineId: (string) $this->input('lineId'),
        );
    }
}
