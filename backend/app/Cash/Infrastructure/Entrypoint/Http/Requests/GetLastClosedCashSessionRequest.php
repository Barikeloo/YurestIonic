<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\GetLastClosedCashSession\GetLastClosedCashSessionCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class GetLastClosedCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toCommand(): GetLastClosedCashSessionCommand
    {
        $restaurantUuid = app(TenantContext::class)->restaurantUuid()
            ?? throw new \RuntimeException('Tenant context is required.');

        return new GetLastClosedCashSessionCommand(
            restaurantId: $restaurantUuid,
        );
    }
}
