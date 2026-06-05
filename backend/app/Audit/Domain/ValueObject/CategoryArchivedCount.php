<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final readonly class CategoryArchivedCount
{
    public function __construct(
        public string $category,
        public int $count,
    ) {}
}
