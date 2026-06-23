<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetTableQrToken;

final readonly class GetTableQrTokenResponse
{
    private function __construct(
        public string $id,
        public string $tableId,
        public string $token,
        public int $catalogVersion,
        public string $url,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $tableId,
        string $token,
        int $catalogVersion,
        string $url,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            tableId: $tableId,
            token: $token,
            catalogVersion: $catalogVersion,
            url: $url,
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
            'updated_at'      => $this->updatedAt,
        ];
    }
}
