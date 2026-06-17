<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http\Requests;

use App\Printer\Application\UpsertPrinterConfig\UpsertPrinterConfigCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;

final class UpsertPrinterConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'ip'         => ['required', 'string', 'max:45'],
            'port'       => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'paper_width'=> ['sometimes', 'integer', 'in:58,80'],
            'enabled'    => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'zone_uuid'  => ['nullable', 'uuid'],
        ];
    }

    public function toCommand(?string $uuid = null): UpsertPrinterConfigCommand
    {
        /** @var TenantContext $tenant */
        $tenant = app(TenantContext::class);

        return new UpsertPrinterConfigCommand(
            restaurantId: $tenant->requireRestaurantId(),
            uuid:         $uuid,
            name:         $this->string('name')->toString(),
            ip:           $this->string('ip')->toString(),
            port:         $this->integer('port', 9100),
            paperWidth:   $this->integer('paper_width', 80),
            enabled:      $this->boolean('enabled', true),
            isDefault:    $this->boolean('is_default', false),
            zoneUuid:     $this->input('zone_uuid'),
        );
    }
}
