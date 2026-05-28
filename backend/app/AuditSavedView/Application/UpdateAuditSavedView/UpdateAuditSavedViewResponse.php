<?php

declare(strict_types=1);

namespace App\AuditSavedView\Application\UpdateAuditSavedView;

final readonly class UpdateAuditSavedViewResponse
{
    /**
     * @param array<string, mixed> $filters
     */
    private function __construct(
        public string $uuid,
        public string $name,
        public ?string $icon,
        public array $filters,
        public string $updatedAt,
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public static function create(
        string $uuid,
        string $name,
        ?string $icon,
        array $filters,
        string $updatedAt,
    ): self {
        return new self(
            uuid: $uuid,
            name: $name,
            icon: $icon,
            filters: $filters,
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
            'updated_at' => $this->updatedAt,
        ];
    }
}
