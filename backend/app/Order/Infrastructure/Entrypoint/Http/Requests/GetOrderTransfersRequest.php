<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\GetOrderTransfers\GetOrderTransfersCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetOrderTransfersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetOrderTransfersCommand
    {
        return new GetOrderTransfersCommand(
            orderId: (string) $this->route('id'),
        );
    }
}
