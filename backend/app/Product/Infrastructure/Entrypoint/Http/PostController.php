<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\CreateProduct\CreateProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController
{
    public function __construct(
        private CreateProduct $createProduct,
    ) {}

    public function __invoke(Request $request): JsonResponse
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
            'active' => ['sometimes', 'boolean'],
        ]);

        $response = ($this->createProduct)(
            familyId: $validated['family_id'],
            taxId: $validated['tax_id'],
            imageSrc: $validated['image_src'] ?? null,
            name: $validated['name'],
            price: $validated['price'],
            stock: $validated['stock'],
            active: $validated['active'] ?? true,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
