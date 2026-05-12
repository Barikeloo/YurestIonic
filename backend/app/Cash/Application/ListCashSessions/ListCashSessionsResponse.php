<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashSessions;

final readonly class ListCashSessionsResponse
{
    /**
     * @param  list<ListCashSessionsItemResponse>  $sessions
     */
    private function __construct(
        public array $sessions,
    ) {}

    /**
     * @param  list<ListCashSessionsItemResponse>  $sessions
     */
    public static function create(array $sessions): self
    {
        return new self(
            sessions: $sessions,
        );
    }

    public function toArray(): array
    {
        return [
            'sessions' => array_map(
                static fn (ListCashSessionsItemResponse $item): array => $item->toArray(),
                $this->sessions,
            ),
        ];
    }
}
