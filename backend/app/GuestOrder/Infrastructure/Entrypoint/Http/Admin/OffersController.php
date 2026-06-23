<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Admin;

use App\GuestOrder\Domain\Interfaces\CustomerOfferRepositoryInterface;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OffersController
{
    public function __construct(
        private readonly CustomerOfferRepositoryInterface $offerRepository,
    ) {}

    public function index(): JsonResponse
    {
        $rid = $this->restaurantId();
        if (!$rid) return new JsonResponse(['message' => 'Tenant required.'], 401);

        return new JsonResponse($this->offerRepository->list($rid), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $rid = $this->restaurantId();
        if (!$rid) return new JsonResponse(['message' => 'Tenant required.'], 401);

        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:150'],
            'description'    => ['nullable', 'string'],
            'discount_type'  => ['required', 'string', 'in:percent,fixed_cents,points_multiplier'],
            'discount_value' => ['required', 'integer', 'min:1'],
            'min_points'     => ['nullable', 'integer', 'min:0'],
            'valid_from'     => ['nullable', 'date'],
            'valid_until'    => ['nullable', 'date'],
        ]);

        try {
            $offer = $this->offerRepository->create($rid, $validated);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($offer, 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $rid = $this->restaurantId();
        if (!$rid) return new JsonResponse(['message' => 'Tenant required.'], 401);

        $validated = $request->validate([
            'title'          => ['sometimes', 'string', 'max:150'],
            'description'    => ['nullable', 'string'],
            'discount_type'  => ['sometimes', 'string', 'in:percent,fixed_cents,points_multiplier'],
            'discount_value' => ['sometimes', 'integer', 'min:1'],
            'min_points'     => ['nullable', 'integer', 'min:0'],
            'valid_from'     => ['nullable', 'date'],
            'valid_until'    => ['nullable', 'date'],
            'active'         => ['sometimes', 'boolean'],
        ]);

        $offer = $this->offerRepository->update($uuid, $rid, $validated);
        if (!$offer) return new JsonResponse(['message' => 'Offer not found.'], 404);

        return new JsonResponse($offer, 200);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $rid = $this->restaurantId();
        if (!$rid) return new JsonResponse(['message' => 'Tenant required.'], 401);

        $deleted = $this->offerRepository->delete($uuid, $rid);
        if (!$deleted) return new JsonResponse(['message' => 'Offer not found.'], 404);

        return new JsonResponse(null, 204);
    }

    private function restaurantId(): ?string
    {
        return app(TenantContext::class)->restaurantUuid();
    }
}
