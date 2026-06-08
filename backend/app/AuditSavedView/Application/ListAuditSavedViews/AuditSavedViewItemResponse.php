<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\ListAuditSavedViews;

final readonly class AuditSavedViewItemResponse
{

    private function __construct(
        public string $uuid,
        public string $name,
        public ?string $icon,
        public array $filters,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $uuid,
        string $name,
        ?string $icon,
        array $filters,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            uuid: $uuid,
            name: $name,
            icon: $icon,
            filters: $filters,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'icon' => $this->icon,
            'filters' => $this->filters,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
