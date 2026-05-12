<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\OpenCashSession\OpenCashSession;
use App\Cash\Domain\Exception\ActiveCashSessionAlreadyExistsException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\OpenCashSessionRequest;
use Illuminate\Http\JsonResponse;

final class OpenCashSessionController
{
    public function __construct(
        private readonly OpenCashSession $openCashSession,
    ) {}

    public function __invoke(OpenCashSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->openCashSession)($request->toCommand());
        } catch (ActiveCashSessionAlreadyExistsException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
