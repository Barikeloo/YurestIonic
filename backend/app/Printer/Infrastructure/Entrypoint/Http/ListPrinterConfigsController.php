<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http;

use App\Printer\Application\ListPrinterConfigs\ListPrinterConfigs;
use App\Printer\Application\ListPrinterConfigs\ListPrinterConfigsCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final class ListPrinterConfigsController
{
    public function __construct(
        private readonly ListPrinterConfigs $listPrinterConfigs,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(): JsonResponse
    {
        $data = ($this->listPrinterConfigs)(
            new ListPrinterConfigsCommand($this->tenantContext->requireRestaurantId())
        );

        return new JsonResponse($data);
    }
}
