<?php

namespace App\SuperAdmin\Application\GetSuperAdminMe;

final readonly class GetSuperAdminMeCommand
{
    public function __construct(
        public ?string $superAdminId,
    ) {}
}
