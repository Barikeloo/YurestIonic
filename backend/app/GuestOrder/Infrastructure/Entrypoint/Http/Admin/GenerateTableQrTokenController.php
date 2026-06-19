<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Entrypoint\Http\Admin;

use App\GuestOrder\Application\GenerateTableQrToken\GenerateTableQrToken;
use App\GuestOrder\Domain\Exception\TableNotFoundException;
use App\GuestOrder\Infrastructure\Entrypoint\Http\Admin\Requests\GenerateTableQrTokenRequest;
use Illuminate\Http\JsonResponse;

final class GenerateTableQrTokenController
{
    public function __construct(
        private readonly GenerateTableQrToken $generateTableQrToken,
    ) {}

    public function __invoke(GenerateTableQrTokenRequest $request): JsonResponse
    {
        try {
            $response = ($this->generateTableQrToken)($request->toCommand());
        } catch (TableNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
