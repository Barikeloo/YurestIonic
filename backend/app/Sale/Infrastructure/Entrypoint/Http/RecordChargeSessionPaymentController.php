<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\RecordChargeSessionPayment\RecordChargeSessionPayment;
use App\Sale\Domain\Exception\ChargeSessionHasNoRemainingDebtException;
use App\Sale\Domain\Exception\ChargeSessionNotActiveException;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Domain\Exception\PaymentAmountExceedsDebtException;
use App\Sale\Domain\Exception\PaymentAmountMustBePositiveException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\RecordChargeSessionPaymentRequest;
use Illuminate\Http\JsonResponse;

final class RecordChargeSessionPaymentController
{
    public function __construct(
        private readonly RecordChargeSessionPayment $recordPayment,
    ) {}

    public function __invoke(RecordChargeSessionPaymentRequest $request): JsonResponse
    {
        try {
            $response = ($this->recordPayment)($request->toCommand());
        } catch (ChargeSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ChargeSessionNotActiveException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (InvalidDinerCountException|ChargeSessionHasNoRemainingDebtException|PaymentAmountMustBePositiveException|PaymentAmountExceedsDebtException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        $statusCode = $response->isSessionComplete ? 200 : 201;

        return new JsonResponse($response->toArray(), $statusCode);
    }
}
