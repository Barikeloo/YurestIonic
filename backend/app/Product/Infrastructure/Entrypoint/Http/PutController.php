<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\UpdateProduct\UpdateProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PutController
{
    public function __construct(
        private UpdateProduct $updateProduct,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'family_id' => [
                'required',
                'uuid',
                Rule::exists('families', 'uuid')->whereNull('deleted_at'),
            ],
            'tax_id' => [
                'required',
                'uuid',
                Rule::exists('taxes', 'uuid')->whereNull('deleted_at'),
            ],
            'image_src' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
        ]);

        $response = ($this->updateProduct)(
            id: $id,
            familyId: $validated['family_id'],
            taxId: $validated['tax_id'],
            imageSrc: $validated['image_src'] ?? null,
            name: $validated['name'],
            price: $validated['price'],
            stock: $validated['stock'],
            active: $validated['active'],
        );

        if ($response === null) {
            return new JsonResponse(['message' => 'Product not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
