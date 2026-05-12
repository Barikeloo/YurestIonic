<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\StartClosingCashSession\StartClosingCashSession;
use App\Cash\Domain\Exception\CashSessionCannotStartClosingException;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\StartClosingCashSessionRequest;
use Illuminate\Http\JsonResponse;

final class StartClosingCashSessionController
{
    public function __construct(
        private readonly StartClosingCashSession $startClosingCashSession,
    ) {}

    public function __invoke(StartClosingCashSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->startClosingCashSession)($request->toCommand());
        } catch (CashSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (CashSessionCannotStartClosingException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
