<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http\Requests;

use App\Order\Application\ReopenOrder\ReopenOrderCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ReopenOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'uuid'],
            'reopened_by_user_id' => ['required', 'string', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function toCommand(): ReopenOrderCommand
    {
        return new ReopenOrderCommand(
            id: (string) $this->input('id'),
            reopenedByUserId: (string) $this->input('reopened_by_user_id'),
        );
    }
}
