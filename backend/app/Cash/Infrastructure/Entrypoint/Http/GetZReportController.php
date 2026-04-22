<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetZReport\GetZReport;
use Illuminate\Http\JsonResponse;

final class GetZReportController
{
    public function __construct(
        private readonly GetZReport $getZReport,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getZReport)($id);

        if ($response === null) {
            return new JsonResponse(['error' => 'Z-Report not found'], 404);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
