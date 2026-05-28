<?php

declare(strict_types=1);

namespace App\Audit\Application\ListAuditEvents;

final readonly class ListAuditEventsResponse
{
    /**
     * @param  list<AuditEventItemResponse>  $items
     */
    private function __construct(
        public array $items,
        public ?string $nextCursor,
        public bool $hasMore,
    ) {}

    /**
     * @param  list<AuditEventItemResponse>  $items
     */
    public static function create(
        array $items,
        ?string $nextCursor,
        bool $hasMore,
    ): self {
        return new self(
            items: $items,
            nextCursor: $nextCursor,
            hasMore: $hasMore,
        );
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(
                static fn (AuditEventItemResponse $item): array => $item->toArray(),
                $this->items,
            ),
            'next_cursor' => $this->nextCursor,
            'has_more' => $this->hasMore,
        ];
    }
}
