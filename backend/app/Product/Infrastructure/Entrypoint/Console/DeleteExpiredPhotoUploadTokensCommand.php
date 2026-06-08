<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Console;

use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use Illuminate\Console\Command;

final class DeleteExpiredPhotoUploadTokensCommand extends Command
{
    protected $signature = 'product-photos:delete-expired-tokens';

    protected $description = 'Deletes expired product photo upload tokens from the database.';

    public function handle(ProductPhotoUploadTokenRepositoryInterface $repository): int
    {
        $deleted = $repository->deleteExpired();
        $this->info("Deleted {$deleted} expired photo upload token(s).");

        return self::SUCCESS;
    }
}
