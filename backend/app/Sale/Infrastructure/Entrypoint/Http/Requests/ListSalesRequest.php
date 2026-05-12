<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\ListSales\ListSalesCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): ListSalesCommand
    {
        return new ListSalesCommand;
    }
}
