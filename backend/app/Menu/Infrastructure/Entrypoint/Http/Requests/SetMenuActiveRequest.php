<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http\Requests;

use App\Menu\Application\SetMenuActive\SetMenuActiveCommand;
use Illuminate\Foundation\Http\FormRequest;

final class SetMenuActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id, bool $active): SetMenuActiveCommand
    {
        return new SetMenuActiveCommand(
            id: $id,
            active: $active,
        );
    }
}
