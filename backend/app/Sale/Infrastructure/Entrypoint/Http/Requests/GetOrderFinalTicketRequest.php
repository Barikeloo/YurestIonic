<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\GetOrderFinalTicket\GetOrderFinalTicketCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetOrderFinalTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetOrderFinalTicketCommand
    {
        return new GetOrderFinalTicketCommand(
            orderId: (string) $this->route('id'),
        );
    }
}
