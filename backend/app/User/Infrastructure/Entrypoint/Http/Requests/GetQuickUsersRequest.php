<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\GetQuickUsers\GetQuickUsersCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetQuickUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:100'],
            'restaurant_uuid' => ['sometimes', 'nullable', 'string', 'uuid'],
        ];
    }

    public function toCommand(): GetQuickUsersCommand
    {
        $restaurantUuid = $this->input('restaurant_uuid');

        return new GetQuickUsersCommand(
            deviceId: (string) $this->input('device_id'),
            restaurantUuid: is_string($restaurantUuid) ? $restaurantUuid : null,
        );
    }
}
