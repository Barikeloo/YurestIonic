<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\AddLineToSale\AddLineToSaleCommand;
use Illuminate\Foundation\Http\FormRequest;

final class AddLineToSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'string', 'uuid'],
            'sale_id' => ['required', 'string', 'uuid'],
            'order_line_id' => ['required', 'string', 'uuid'],
            'user_id' => ['required', 'string', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'integer', 'min:0'],
            'tax_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function toCommand(): AddLineToSaleCommand
    {
        return new AddLineToSaleCommand(
            restaurantId: (string) $this->input('restaurant_id'),
            saleId: (string) $this->input('sale_id'),
            orderLineId: (string) $this->input('order_line_id'),
            userId: (string) $this->input('user_id'),
            quantity: (int) $this->input('quantity'),
            price: (int) $this->input('price'),
            taxPercentage: (int) $this->input('tax_percentage'),
        );
    }
}
