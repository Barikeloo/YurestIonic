<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http\Requests;

use App\Product\Application\UpdateProductAvailability\UpdateProductAvailabilityCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'available' => ['required', 'boolean'],
        ];
    }

    public function toCommand(): UpdateProductAvailabilityCommand
    {
        return new UpdateProductAvailabilityCommand(
            productId: (string) $this->route('id'),
            available: (bool) $this->input('available'),
        );
    }
}
