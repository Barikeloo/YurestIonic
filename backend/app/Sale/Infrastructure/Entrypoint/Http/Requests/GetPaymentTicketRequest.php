<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\GetPaymentTicket\GetPaymentTicketCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetPaymentTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetPaymentTicketCommand
    {
        return new GetPaymentTicketCommand(
            saleId: (string) $this->route('id'),
        );
    }
}
