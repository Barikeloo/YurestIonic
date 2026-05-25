<?php

namespace App\Order\Application\GetOrderPreTicket;

final readonly class GetOrderPreTicketResponse
{
    public function __construct(
        public string $text,
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
        ];
    }
}
