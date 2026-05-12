<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetFinalTicketPrint\GetFinalTicketPrint;
use App\Sale\Domain\Exception\OrderFinalTicketNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\GetFinalTicketPrintRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GetFinalTicketPrintController
{
    public function __construct(
        private readonly GetFinalTicketPrint $getFinalTicketPrint,
        private readonly TicketTextFormatter $ticketTextFormatter,
    ) {}

    public function __invoke(GetFinalTicketPrintRequest $request): JsonResponse|Response
    {
        try {
            $response = ($this->getFinalTicketPrint)($request->toCommand());
        } catch (OrderFinalTicketNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        $format = strtolower((string) $request->query('format', 'json'));
        if ($format === 'text') {
            $width = $this->ticketTextFormatter->resolveWidth((string) $request->query('width'));
            $text = $this->ticketTextFormatter->formatFinal($response->toArray(), $width);

            return new Response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
