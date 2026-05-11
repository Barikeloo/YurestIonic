<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http\Requests;

use App\SuperAdmin\Application\GetSuperAdminMe\GetSuperAdminMeCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetSuperAdminMeCommand
    {
        $superAdminId = $this->session()->get('super_admin_id');

        return new GetSuperAdminMeCommand(
            superAdminId: is_string($superAdminId) ? $superAdminId : null,
        );
    }
}
