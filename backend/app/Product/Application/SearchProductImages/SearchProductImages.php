<?php

declare(strict_types=1);

namespace App\Product\Application\SearchProductImages;

use App\Product\Domain\Interfaces\ImageSearchServiceInterface;

final class SearchProductImages
{
    public function __construct(
        private readonly ImageSearchServiceInterface $imageSearchService,
    ) {}

    /**
     * @return array<array{url: string, thumbUrl: string, alt: string, source: string, author: string, authorUrl: string}>
     */
    public function __invoke(string $query, ?int $limit = null): array
    {
        $searchTerm = trim($query);
        
        if ($searchTerm === '') {
            return [];
        }

        // Limpiar y preparar término de búsqueda
        $searchTerm = $this->sanitizeQuery($searchTerm);

        return $this->imageSearchService->search($searchTerm, $limit ?? 6);
    }

    private function sanitizeQuery(string $query): string
    {
        // Eliminar caracteres especiales que puedan causar problemas
        $query = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $query);
        
        // Limitar longitud
        if (mb_strlen($query) > 100) {
            $query = mb_substr($query, 0, 100);
        }

        return trim($query);
    }
}
