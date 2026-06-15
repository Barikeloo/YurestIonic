<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http\Requests;

use App\Sale\Application\CreateChargeSession\CreateChargeSessionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class CreateChargeSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'diners_count' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toCommand(string $restaurantId): CreateChargeSessionCommand
    {
        return new CreateChargeSessionCommand(
            restaurantId: $restaurantId,
            orderId: (string) $this->input('order_id'),
            openedByUserId: (string) $this->input('opened_by_user_id'),
            dinersCount: $this->input('diners_count') ? (int) $this->input('diners_count') : null,
        );
    }
}
