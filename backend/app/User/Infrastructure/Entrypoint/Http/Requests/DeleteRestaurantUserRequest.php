<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\DeleteRestaurantUser\DeleteRestaurantUserCommand;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteRestaurantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(
        string $restaurantUuid,
        string $userUuid,
        ?string $actorUserUuid,
        ?string $actorSuperAdminUuid,
    ): DeleteRestaurantUserCommand {
        $deviceId = $this->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $this->header('X-Device-Id');
        }

        return new DeleteRestaurantUserCommand(
            restaurantUuid: $restaurantUuid,
            userUuid: $userUuid,
            actorUserUuid: $actorUserUuid,
            actorSuperAdminUuid: $actorSuperAdminUuid,
            deviceId: is_string($deviceId) ? $deviceId : null,
            ipAddress: $this->ip(),
        );
    }
}
