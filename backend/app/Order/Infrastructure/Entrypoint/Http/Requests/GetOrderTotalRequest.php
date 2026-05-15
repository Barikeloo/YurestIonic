<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\GetOrderTotal\GetOrderTotalCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetOrderTotalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orderId' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'orderId' => $this->route('id'),
        ]);
    }

    public function toCommand(): GetOrderTotalCommand
    {
        return new GetOrderTotalCommand(
            orderId: (string) $this->input('orderId'),
        );
    }
}
