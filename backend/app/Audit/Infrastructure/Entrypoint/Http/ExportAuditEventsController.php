<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Application\ExportAuditEvents\ExportAuditEvents;
use App\Audit\Application\ExportAuditEvents\ExportAuditEventsCommand;
use App\Audit\Domain\ValueObject\ExportFormat;
use App\Audit\Infrastructure\Entrypoint\Http\Requests\ExportAuditEventsRequest;
use App\Audit\Infrastructure\Export\AuditExportFormatter;
use App\Audit\Infrastructure\Export\CsvAuditExportFormatter;
use App\Audit\Infrastructure\Export\NdjsonAuditExportFormatter;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportAuditEventsController
{
    public function __construct(
        private readonly ExportAuditEvents $useCase,
    ) {}

    public function __invoke(ExportAuditEventsRequest $request): StreamedResponse
    {
        $command = $request->toCommand();
        $formatter = $this->formatterFor($command->format);

        return new StreamedResponse(
            function () use ($command, $formatter): void {
                echo $formatter->header();
                flush();

                foreach (($this->useCase)($command) as $log) {
                    echo $formatter->formatRow($log);
                    flush();
                }

                echo $formatter->footer();
                flush();
            },
            200,
            [
                'Content-Type' => $command->format->contentType(),
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $this->buildFilename($command),
                ),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'X-Accel-Buffering' => 'no',
            ],
        );
    }

    private function formatterFor(ExportFormat $format): AuditExportFormatter
    {
        return match ($format) {
            ExportFormat::Csv => new CsvAuditExportFormatter,
            ExportFormat::Ndjson => new NdjsonAuditExportFormatter,
        };
    }

    private function buildFilename(ExportAuditEventsCommand $command): string
    {
        $stamp = (new \DateTimeImmutable)->format('Y-m-d-His');

        return sprintf('audit-log-%s.%s', $stamp, $command->format->fileExtension());
    }
}
