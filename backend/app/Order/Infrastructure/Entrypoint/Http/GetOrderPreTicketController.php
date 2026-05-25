<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderPreTicket\GetOrderPreTicket;
use App\Order\Infrastructure\Entrypoint\Http\Requests\GetOrderPreTicketRequest;
use Illuminate\Http\Response;

final class GetOrderPreTicketController
{
    public function __construct(
        private readonly GetOrderPreTicket $getOrderPreTicket,
    ) {}

    public function __invoke(GetOrderPreTicketRequest $request): Response
    {
        $response = ($this->getOrderPreTicket)($request->toCommand());

        return new Response(
            $response->text,
            200,
            ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}
