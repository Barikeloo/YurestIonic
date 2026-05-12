<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\DeleteSale\DeleteSaleCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): DeleteSaleCommand
    {
        return new DeleteSaleCommand(
            id: (string) $this->route('id'),
        );
    }
}
