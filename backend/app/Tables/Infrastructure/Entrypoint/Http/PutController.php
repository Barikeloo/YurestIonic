<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Shared\Infrastructure\Tenant\TenantContext;
use App\Tables\Application\UpdateTable\UpdateTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PutController
{
    public function __construct(
        private UpdateTable $updateTable,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => [
                'required',
                'uuid',
                Rule::exists('zones', 'uuid')
                    ->where('restaurant_id', $this->tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $response = ($this->updateTable)($id, $validated['zone_id'], $validated['name']);

        if ($response === null) {
            return new JsonResponse(['message' => 'Table not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
