<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GenerateTableQrToken;

final readonly class GenerateTableQrTokenResponse
{
    private function __construct(
        public string $id,
        public string $tableId,
        public string $token,
        public int $catalogVersion,
        public string $url,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $tableId,
        string $token,
        int $catalogVersion,
        string $url,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            tableId: $tableId,
            token: $token,
            catalogVersion: $catalogVersion,
            url: $url,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'table_id'        => $this->tableId,
            'token'           => $this->token,
            'catalog_version' => $this->catalogVersion,
            'url'             => $this->url,
            'created_at'      => $this->createdAt,
            'updated_at'      => $this->updatedAt,
        ];
    }
}
