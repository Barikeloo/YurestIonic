<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http\Requests;

use App\Menu\Application\ArchiveMenu\ArchiveMenuCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ArchiveMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(string $id): ArchiveMenuCommand
    {
        return new ArchiveMenuCommand(
            id: $id,
        );
    }
}
