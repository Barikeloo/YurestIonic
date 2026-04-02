<?php

namespace App\Shared\Domain\Interfaces;

interface TransactionManagerInterface
{
    public function run(callable $callback): mixed;
}
