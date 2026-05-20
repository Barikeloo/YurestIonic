<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\TransferOrder\TransferOrder;
use App\Order\Domain\Exception\DestinationTableOccupiedException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\TransferOrderRequest;
use App\Tables\Domain\Exception\TableNotFoundException;
use Illuminate\Http\JsonResponse;

final class TransferOrderController
{
    public function __construct(
        private readonly TransferOrder $useCase,
    ) {}

    public function __invoke(TransferOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (OrderNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (TableNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (DestinationTableOccupiedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
