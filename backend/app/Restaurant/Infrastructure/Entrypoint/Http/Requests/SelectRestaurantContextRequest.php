<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http\Requests;

use App\Restaurant\Application\SelectRestaurantContext\SelectRestaurantContextCommand;
use Illuminate\Foundation\Http\FormRequest;

final class SelectRestaurantContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function toCommand(): SelectRestaurantContextCommand
    {
        $superAdminUuid = $this->session()->get('super_admin_id');
        $authUserUuid = $this->session()->get('auth_user_id');

        return new SelectRestaurantContextCommand(
            authUserUuid: is_string($authUserUuid) ? $authUserUuid : null,
            targetRestaurantUuid: (string) $this->input('restaurant_id'),
            isSuperAdmin: is_string($superAdminUuid) && $superAdminUuid !== '',
        );
    }
}
