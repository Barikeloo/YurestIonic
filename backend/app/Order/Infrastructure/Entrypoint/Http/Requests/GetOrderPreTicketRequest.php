<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\GetOrderPreTicket\GetOrderPreTicketCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetOrderPreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
            'format' => ['sometimes', 'string', 'in:text,json'],
            'width' => ['sometimes', 'string', 'in:58,80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function toCommand(): GetOrderPreTicketCommand
    {
        return new GetOrderPreTicketCommand(
            orderId: (string) $this->input('id'),
            format: (string) $this->input('format', 'text'),
            width: (string) $this->input('width', '80'),
        );
    }
}
