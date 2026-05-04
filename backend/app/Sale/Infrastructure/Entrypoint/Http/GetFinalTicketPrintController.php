<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetFinalTicketPrint\GetFinalTicketPrint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class GetFinalTicketPrintController
{
    public function __construct(
        private readonly GetFinalTicketPrint $getFinalTicketPrint,
        private readonly TicketTextFormatter $ticketTextFormatter,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse|Response
    {
        $response = ($this->getFinalTicketPrint)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Final ticket not found.'], 404);
        }

        $format = strtolower((string) $request->query('format', 'json'));
        if ($format === 'text') {
            $width = $this->ticketTextFormatter->resolveWidth((string) $request->query('width'));
            $text = $this->ticketTextFormatter->formatFinal($response->toArray(), $width);

            return new Response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new JsonResponse($response->toArray());
    }
}
