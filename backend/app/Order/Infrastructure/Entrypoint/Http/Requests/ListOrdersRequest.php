<?php

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\ListOrders\ListOrdersCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): ListOrdersCommand
    {
        return new ListOrdersCommand;
    }
}
