<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final readonly class TopArchivedUser
{
    public function __construct(
        public string $uuid,
        public string $name,
        public ?string $role,
        public int $count,
    ) {}
}
