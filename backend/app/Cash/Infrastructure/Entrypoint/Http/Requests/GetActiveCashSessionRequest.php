<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http\Requests;

use App\Cash\Application\GetActiveCashSession\GetActiveCashSessionCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class GetActiveCashSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string', 'max:100'],
        ];
    }

    public function toCommand(): GetActiveCashSessionCommand
    {
        $restaurantUuid = app(TenantContext::class)->restaurantUuid()
            ?? throw new \RuntimeException('Tenant context is required.');

        return new GetActiveCashSessionCommand(
            restaurantId: $restaurantUuid,
            deviceId: (string) $this->input('device_id'),
        );
    }
}
