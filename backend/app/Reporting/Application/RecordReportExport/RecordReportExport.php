<?php

declare(strict_types=1);

namespace App\Reporting\Application\RecordReportExport;

use App\Reporting\Domain\Interfaces\ReportExportRepositoryInterface;
use App\Reporting\Domain\Interfaces\ReportExportStorageInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final readonly class RecordReportExport
{
    public function __construct(
        private ReportExportRepositoryInterface $repository,
        private ReportExportStorageInterface    $storage,
        private UserRepositoryInterface         $userRepository,
    ) {}

    public function __invoke(RecordReportExportCommand $command): void
    {
        $uuid      = Uuid::generate()->value();
        $extension = strtolower($command->format);
        $path      = $this->storage->store($command->restaurantId, $uuid, $extension, $command->contents);

        $this->repository->save([
            'uuid'          => $uuid,
            'restaurant_id' => $command->restaurantId,
            'user_uuid'     => $command->userUuid,
            'user_name'     => $this->resolveUserName($command->userUuid),
            'report_type'   => $command->reportType,
            'title'         => $command->title,
            'format'        => $command->format,
            'filename'      => $command->filename,
            'size_bytes'    => strlen($command->contents),
            'storage_path'  => $path,
        ]);
    }

    private function resolveUserName(?string $userUuid): string
    {
        if ($userUuid === null || $userUuid === '') {
            return 'Sistema';
        }

        $user = $this->userRepository->findById($userUuid);

        return $user?->name()->value() ?? 'Sistema';
    }
}
