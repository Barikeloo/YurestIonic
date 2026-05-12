<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\GetSale\GetSaleCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetSaleCommand
    {
        return new GetSaleCommand(
            id: (string) $this->route('id'),
        );
    }
}
