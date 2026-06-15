<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http\Requests;

use App\Restaurant\Application\UpdateRestaurant\UpdateRestaurantCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'legal_name' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'min:1', 'max:255'],
            'email' => ['sometimes', 'string', 'email'],
            'password' => ['sometimes', 'string', 'min:8'],
        ];
    }

    public function toCommand(string $id): UpdateRestaurantCommand
    {
        $superAdminUuid = $this->session()->get('super_admin_id');
        $authUserUuid = $this->session()->get('auth_user_id');
        $isSuperAdmin = is_string($superAdminUuid) && $superAdminUuid !== '';

        return new UpdateRestaurantCommand(
            id: $id,
            name: $this->input('name'),
            legalName: $this->input('legal_name'),
            taxId: $this->input('tax_id'),
            email: $this->input('email'),
            plainPassword: $this->input('password'),
            authUserUuid: is_string($authUserUuid) ? $authUserUuid : null,
            isSuperAdmin: $isSuperAdmin,
        );
    }
}
