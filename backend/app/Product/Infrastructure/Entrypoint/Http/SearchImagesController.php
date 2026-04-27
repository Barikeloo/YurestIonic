<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\SearchProductImages\SearchProductImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchImagesController
{
    public function __construct(
        private readonly SearchProductImages $searchProductImages,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $limit = $request->query('limit');

        $images = ($this->searchProductImages)(
            query: $query,
            limit: $limit !== null ? (int) $limit : null,
        );

        return new JsonResponse([
            'query' => $query,
            'images' => $images,
        ]);
    }
}
