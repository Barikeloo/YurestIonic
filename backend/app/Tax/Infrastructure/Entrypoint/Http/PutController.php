<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\UpdateTax\UpdateTax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PutController
{
    public function __construct(
        private UpdateTax $updateTax,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taxes', 'name')->ignore($id, 'uuid')->whereNull('deleted_at'),
            ],
            'percentage' => ['required', 'integer', 'between:0,100'],
        ]);

        $response = ($this->updateTax)($id, $validated['name'], $validated['percentage']);

        if ($response === null) {
            return new JsonResponse(['message' => 'Tax not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
