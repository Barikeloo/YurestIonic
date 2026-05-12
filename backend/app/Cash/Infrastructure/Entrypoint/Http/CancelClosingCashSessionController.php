<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\CancelClosingCashSession\CancelClosingCashSession;
use App\Cash\Domain\Exception\CashSessionCannotCancelClosingException;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\CancelClosingCashSessionRequest;
use Illuminate\Http\JsonResponse;

final class CancelClosingCashSessionController
{
    public function __construct(
        private readonly CancelClosingCashSession $cancelClosingCashSession,
    ) {}

    public function __invoke(CancelClosingCashSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->cancelClosingCashSession)($request->toCommand());
        } catch (CashSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (CashSessionCannotCancelClosingException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
