<?php

declare(strict_types=1);

namespace App\Printer\Infrastructure\Entrypoint\Http;

use App\Printer\Application\PrintFinalTicket\PrintFinalTicket;
use App\Printer\Application\PrintFinalTicket\PrintFinalTicketCommand;
use App\Printer\Domain\Exception\PrinterConnectionException;
use App\Sale\Domain\Exception\OrderFinalTicketNotFoundException;
use Illuminate\Http\JsonResponse;

final class PrintTicketController
{
    public function __construct(
        private readonly PrintFinalTicket $printFinalTicket,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            ($this->printFinalTicket)(new PrintFinalTicketCommand($id));
        } catch (OrderFinalTicketNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (PrinterConnectionException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(['message' => 'Ticket sent to printer.']);
    }
}
