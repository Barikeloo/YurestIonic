<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateCreditNote\CreateCreditNote;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreateCreditNoteController
{
    public function __construct(
        private readonly CreateCreditNote $createCreditNote,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => ['required', 'string', 'uuid'],
            'parent_sale_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'total_cents' => ['required', 'integer', 'min:1'],
            'customer_fiscal_data' => ['sometimes', 'array', 'nullable'],
        ]);

        $response = ($this->createCreditNote)(
            restaurantId: $this->tenantContext->restaurantUuid(),
            orderId: $request->input('order_id'),
            parentSaleId: $request->input('parent_sale_id'),
            openedByUserId: $request->input('opened_by_user_id'),
            totalCents: $request->input('total_cents'),
            customerFiscalData: $request->input('customer_fiscal_data'),
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
