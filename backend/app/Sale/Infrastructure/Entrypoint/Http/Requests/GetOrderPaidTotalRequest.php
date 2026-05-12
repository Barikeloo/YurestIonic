<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\GetOrderPaidTotal\GetOrderPaidTotalCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetOrderPaidTotalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetOrderPaidTotalCommand
    {
        return new GetOrderPaidTotalCommand(
            orderId: (string) $this->route('orderId'),
        );
    }
}
