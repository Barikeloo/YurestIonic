<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\ForceCloseCashSession\ForceCloseCashSession;
use App\Cash\Domain\Exception\CashSessionAlreadyClosedException;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\ForceCloseCashSessionRequest;
use Illuminate\Http\JsonResponse;

final class ForceCloseCashSessionController
{
    public function __construct(
        private readonly ForceCloseCashSession $forceCloseCashSession,
    ) {}

    public function __invoke(ForceCloseCashSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->forceCloseCashSession)($request->toCommand());
        } catch (CashSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (CashSessionAlreadyClosedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
