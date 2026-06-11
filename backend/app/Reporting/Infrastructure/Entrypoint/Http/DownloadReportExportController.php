<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\DownloadReportExport\DownloadReportExport;
use App\Reporting\Application\DownloadReportExport\DownloadReportExportCommand;
use App\Reporting\Domain\Exception\ReportExportNotFoundException;
use App\Shared\Infrastructure\Tenant\TenantContext;

final readonly class DownloadReportExportController
{
    public function __construct(private DownloadReportExport $useCase) {}

    public function __invoke(string $uuid)
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new DownloadReportExportCommand(
                restaurantId: $restaurantId,
                uuid:         $uuid,
            ));

            return response($response->contents, 200, [
                'Content-Type'        => $response->mimeType,
                'Content-Disposition' => 'attachment; filename="' . $response->filename . '"',
            ]);
        } catch (ReportExportNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
