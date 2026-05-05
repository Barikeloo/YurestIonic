<?php

namespace App\User\Infrastructure\Entrypoint\Http\Requests;

use App\User\Application\GetRestaurantUsers\GetRestaurantUsersCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetRestaurantUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $restaurantUuid): GetRestaurantUsersCommand
    {
        return new GetRestaurantUsersCommand(restaurantUuid: $restaurantUuid);
    }
}
