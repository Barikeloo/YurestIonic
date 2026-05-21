<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http\Requests;

use App\Menu\Application\GetMenu\GetMenuCommand;
use Illuminate\Foundation\Http\FormRequest;

final class GetMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): GetMenuCommand
    {
        return new GetMenuCommand(id: $id);
    }
}
