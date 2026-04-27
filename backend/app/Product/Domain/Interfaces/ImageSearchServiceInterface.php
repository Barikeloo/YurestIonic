<?php

declare(strict_types=1);

namespace App\Product\Domain\Interfaces;

interface ImageSearchServiceInterface
{
    /**
     * Busca imágenes por término de búsqueda.
     *
     * @return array<array{url: string, thumbUrl: string, alt: string, source: string}>
     */
    public function search(string $query, int $perPage = 6): array;
}
