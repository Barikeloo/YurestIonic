<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Services;

use App\Product\Domain\Interfaces\ImageSearchServiceInterface;
use Illuminate\Support\Facades\Http;

final class SpoonacularImageSearchService implements ImageSearchServiceInterface
{
    private readonly string $apiKey;
    private readonly string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.spoonacular.api_key', '');
        $this->baseUrl = 'https://api.spoonacular.com';
    }

    public function search(string $query, int $perPage = 6): array
    {
        if ($this->apiKey === '') {
            \Log::debug('[Spoonacular] API key no configurada');
            return [];
        }

        try {
            \Log::debug('[Spoonacular] Buscando: ' . $query);
            
            // Spoonacular: buscar ingredientes/grocery products con imágenes
            $response = Http::get($this->baseUrl . '/food/ingredients/search', [
                'apiKey' => $this->apiKey,
                'query' => $query,
                'number' => $perPage,
                'metaInformation' => true,
            ]);

            \Log::debug('[Spoonacular] Respuesta status: ' . $response->status());

            if (! $response->successful()) {
                \Log::debug('[Spoonacular] Error respuesta: ' . $response->body());
                return [];
            }

            $data = $response->json();
            $results = [];

            foreach ($data['results'] ?? [] as $item) {
                $imageName = $item['image'] ?? null;
                if ($imageName) {
                    $results[] = [
                        'url' => "https://spoonacular.com/cdn/ingredients_500x500/{$imageName}",
                        'thumbUrl' => "https://spoonacular.com/cdn/ingredients_100x100/{$imageName}",
                        'alt' => $item['name'] ?? $query,
                        'source' => 'spoonacular',
                        'author' => 'Spoonacular',
                        'authorUrl' => 'https://spoonacular.com',
                    ];
                }
            }

            // Si no hay resultados de ingredients, probar con grocery products
            if (empty($results)) {
                $response = Http::get($this->baseUrl . '/food/products/search', [
                    'apiKey' => $this->apiKey,
                    'query' => $query,
                    'number' => $perPage,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    foreach ($data['products'] ?? [] as $item) {
                        if (! empty($item['image'])) {
                            $results[] = [
                                'url' => $item['image'],
                                'thumbUrl' => $item['image'],
                                'alt' => $item['title'] ?? $query,
                                'source' => 'spoonacular',
                                'author' => 'Spoonacular',
                                'authorUrl' => 'https://spoonacular.com',
                            ];
                        }
                    }
                }
            }

            return $results;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
