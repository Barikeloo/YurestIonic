<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\GetCurrentChargeSession\GetCurrentChargeSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetCurrentChargeSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function toCommand(): GetCurrentChargeSessionCommand
    {
        return new GetCurrentChargeSessionCommand(
            orderId: (string) $this->input('order_id'),
        );
    }
}
