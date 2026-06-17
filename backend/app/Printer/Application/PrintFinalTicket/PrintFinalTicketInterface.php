<?php

declare(strict_types=1);

namespace App\Printer\Application\PrintFinalTicket;

interface PrintFinalTicketInterface
{
    public function __invoke(PrintFinalTicketCommand $command): void;
}
