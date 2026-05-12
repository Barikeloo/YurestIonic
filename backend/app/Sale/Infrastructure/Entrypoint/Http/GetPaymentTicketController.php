<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetPaymentTicket\GetPaymentTicket;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Exception\SalePaymentsNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\GetPaymentTicketRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetPaymentTicketController
{
    public function __construct(
        private readonly GetPaymentTicket $getPaymentTicket,
        private readonly TicketTextFormatter $ticketTextFormatter,
    ) {}

    public function __invoke(GetPaymentTicketRequest $request): JsonResponse|Response
    {
        try {
            $response = ($this->getPaymentTicket)($request->toCommand());
        } catch (SaleNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (SalePaymentsNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        $format = strtolower((string) $request->query('format', 'json'));
        if ($format === 'text') {
            $width = $this->ticketTextFormatter->resolveWidth((string) $request->query('width'));
            $text = $this->ticketTextFormatter->formatPayment($response->toArray(), $width);

            return new Response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
