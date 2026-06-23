<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Admin;

use App\GuestOrder\Application\GetTableQrToken\GetTableQrToken;
use App\GuestOrder\Domain\Exception\TableNotFoundException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Admin\Requests\GetTableQrTokenRequest;
use Illuminate\Http\JsonResponse;

final class GetTableQrTokenController
{
    public function __construct(
        private readonly GetTableQrToken $getTableQrToken,
    ) {}

    public function __invoke(GetTableQrTokenRequest $request): JsonResponse
    {
        try {
            $response = ($this->getTableQrToken)($request->toCommand());
        } catch (TableNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
