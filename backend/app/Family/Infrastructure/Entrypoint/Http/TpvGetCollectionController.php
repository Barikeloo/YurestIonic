<?php

declare(strict_types=1);

namespace App\Family\Infrastructure\Entrypoint\Http;

use App\Family\Application\ListActiveFamilies\ListActiveFamilies;
use Illuminate\Http\JsonResponse;

/**
 * Controller for TPV endpoint that returns only active families.
 */
final class TpvGetCollectionController
{
    public function __construct(
        private ListActiveFamilies $listActiveFamilies,
    ) {}

    public function __invoke(): JsonResponse
    {
        return new JsonResponse(($this->listActiveFamilies)());
    }
}
