<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

final readonly class GetLastClosedCashSessionResponse
{
    private function __construct(
        public ?LastClosedCashSessionItemResponse $lastClosed,
        public ?OrphanCashSessionItemResponse $orphanSession,
    ) {}

    public static function create(
        ?LastClosedCashSessionItemResponse $lastClosed,
        ?OrphanCashSessionItemResponse $orphanSession,
    ): self {
        return new self(
            lastClosed: $lastClosed,
            orphanSession: $orphanSession,
        );
    }

    public function toArray(): array
    {
        return [
            'last_closed' => $this->lastClosed?->toArray(),
            'orphan_session' => $this->orphanSession?->toArray(),
        ];
    }
}
