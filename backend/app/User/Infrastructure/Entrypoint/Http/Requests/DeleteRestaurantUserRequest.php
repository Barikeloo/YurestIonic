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

    public function toCommand(string $restaurantUuid, string $userUuid): DeleteRestaurantUserCommand
    {
        return new DeleteRestaurantUserCommand(
            restaurantUuid: $restaurantUuid,
            userUuid: $userUuid,
        );
    }
}
