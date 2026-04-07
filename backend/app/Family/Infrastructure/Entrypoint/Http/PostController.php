<?php

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\CreateFamily\CreateFamily;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController
{
    public function __construct(
        private CreateFamily $createFamily,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('families', 'name')
                    ->where('restaurant_id', $this->tenantContext->requireRestaurantId())
                    ->whereNull('deleted_at'),
            ],
        ]);

        $response = ($this->createFamily)($validated['name']);

        return new JsonResponse($response->toArray(), 201);
    }
}
