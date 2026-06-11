<?php

declare(strict_types=1);

namespace App\Reporting\Application\DownloadReportExport;

use App\Reporting\Domain\Exception\ReportExportNotFoundException;
use App\Reporting\Domain\Interfaces\ReportExportRepositoryInterface;
use App\Reporting\Domain\Interfaces\ReportExportStorageInterface;

final readonly class DownloadReportExport
{
    public function __construct(
        private ReportExportRepositoryInterface $repository,
        private ReportExportStorageInterface    $storage,
    ) {}

    public function __invoke(DownloadReportExportCommand $command): DownloadReportExportResponse
    {
        $export = $this->repository->findForDownload($command->restaurantId, $command->uuid)
            ?? throw ReportExportNotFoundException::withUuid($command->uuid);

        $contents = $this->storage->read($export['storage_path'])
            ?? throw ReportExportNotFoundException::withUuid($command->uuid);

        return DownloadReportExportResponse::create(
            contents: $contents,
            filename: $export['filename'],
            format:   $export['format'],
        );
    }
}
