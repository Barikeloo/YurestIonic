<?php

namespace App\Family\Application\DeleteFamily;

final readonly class DeleteFamilyCommand
{
    public function __construct(
        public string $id,
    ) {}
}
