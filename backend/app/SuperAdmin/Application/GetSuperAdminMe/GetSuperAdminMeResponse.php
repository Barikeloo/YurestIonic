<?php

namespace App\SuperAdmin\Application\GetSuperAdminMe;

final readonly class GetSuperAdminMeResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}

    public static function create(
        string $id,
        string $name,
        string $email,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
