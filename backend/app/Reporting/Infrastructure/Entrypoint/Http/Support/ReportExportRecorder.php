<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http\Support;

use App\Reporting\Application\RecordReportExport\RecordReportExport;
use App\Reporting\Application\RecordReportExport\RecordReportExportCommand;
use Illuminate\Http\Request;

/**
 * Thin HTTP-adjacent helper that records a generated export. Reads the
 * authenticated user from the session and delegates to the use case.
 * Failures are swallowed: logging an export must never break the download.
 */
final readonly class ReportExportRecorder
{
    public function __construct(private RecordReportExport $useCase) {}

    public function record(
        Request $request,
        int $restaurantId,
        string $type,
        string $title,
        string $format,
        string $filename,
        string $contents,
    ): void {
        try {
            $userUuid = $request->session()->get('auth_user_id');

            ($this->useCase)(new RecordReportExportCommand(
                restaurantId: $restaurantId,
                userUuid:     is_string($userUuid) && $userUuid !== '' ? $userUuid : null,
                reportType:   $type,
                title:        $title,
                format:       $format,
                filename:     $filename,
                contents:     $contents,
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
