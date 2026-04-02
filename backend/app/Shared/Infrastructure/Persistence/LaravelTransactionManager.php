<?php

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
