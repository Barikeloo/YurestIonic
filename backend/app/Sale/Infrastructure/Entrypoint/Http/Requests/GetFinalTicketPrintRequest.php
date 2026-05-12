<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\GetFinalTicketPrint\GetFinalTicketPrintCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetFinalTicketPrintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetFinalTicketPrintCommand
    {
        return new GetFinalTicketPrintCommand(
            orderId: (string) $this->route('id'),
        );
    }
}
