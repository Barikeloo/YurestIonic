<?php

namespace App\Tables\Infrastructure\Entrypoint\Http\Requests;

use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tables\Application\UnmergeTables\UnmergeTablesCommand;
use Illuminate\Foundation\Http\FormRequest;

final class UnmergeTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'string', 'uuid'],
        ];
    }

    public function toCommand(): UnmergeTablesCommand
    {
        $tenantContext = app(TenantContext::class);
        $restaurantId = $tenantContext->restaurantUuid();

        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        return new UnmergeTablesCommand(
            groupId: (string) $this->input('group_id'),
            restaurantId: $restaurantId,
        );
    }
}
