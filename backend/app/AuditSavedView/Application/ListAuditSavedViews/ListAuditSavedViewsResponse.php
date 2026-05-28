<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\ListAuditSavedViews;

final readonly class ListAuditSavedViewsResponse
{
    /**
     * @param list<AuditSavedViewItemResponse> $items
     */
    private function __construct(
        public array $items,
    ) {}

    /**
     * @param list<AuditSavedViewItemResponse> $items
     */
    public static function create(array $items): self
    {
        return new self(items: $items);
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(
                static fn (AuditSavedViewItemResponse $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
