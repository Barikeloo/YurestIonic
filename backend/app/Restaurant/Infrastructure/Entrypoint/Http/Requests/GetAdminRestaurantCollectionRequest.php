<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http\Requests;

use App\Restaurant\Application\GetAdminRestaurantCollection\GetAdminRestaurantCollectionCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetAdminRestaurantCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetAdminRestaurantCollectionCommand
    {
        $superAdminUuid = $this->session()->get('super_admin_id');
        $authUserUuid = $this->session()->get('auth_user_id');

        return new GetAdminRestaurantCollectionCommand(
            authUserUuid: is_string($authUserUuid) ? $authUserUuid : null,
            isSuperAdmin: is_string($superAdminUuid) && $superAdminUuid !== '',
        );
    }
}
