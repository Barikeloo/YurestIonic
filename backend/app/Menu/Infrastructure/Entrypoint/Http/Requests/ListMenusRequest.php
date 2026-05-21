<?php

declare(strict_types=1);

namespace App\Menu\Infrastructure\Entrypoint\Http\Requests;

use App\Menu\Application\ListMenus\ListMenusCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ListMenusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active' => ['sometimes', 'in:true,false,1,0'],
            'archived' => ['sometimes', 'in:true,false,1,0'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function toCommand(): ListMenusCommand
    {
        return new ListMenusCommand(
            active: $this->parseBoolFilter($this->query('active')),
            archived: $this->parseBoolFilter($this->query('archived')),
            search: $this->query('search') !== null && $this->query('search') !== ''
                ? (string) $this->query('search')
                : null,
        );
    }

    private function parseBoolFilter(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
            return true;
        }
        if ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
            return false;
        }

        return null;
    }
}
