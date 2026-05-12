<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\CloseCashSession\CloseCashSession;
use App\Cash\Domain\Exception\CashSessionCannotCloseException;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\PendingSalesPreventClosingException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\CloseCashSessionRequest;
use Illuminate\Http\JsonResponse;

final class CloseCashSessionController
{
    public function __construct(
        private readonly CloseCashSession $closeCashSession,
    ) {}

    public function __invoke(CloseCashSessionRequest $request): JsonResponse
    {
        try {
            $response = ($this->closeCashSession)($request->toCommand());
        } catch (CashSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (PendingSalesPreventClosingException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (CashSessionCannotCloseException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
