<?php

declare(strict_types=1);

namespace App\Reporting\Application\SendScheduledReportNow;

use App\Mail\ScheduledReportMail;
use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Application\Shared\ReportFileGeneratorInterface;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Illuminate\Support\Facades\Mail;

final readonly class SendScheduledReportNow
{
    private const TYPE_LABELS = [
        'daily'    => 'Resumen diario',
        'products' => 'Ventas por producto',
        'families' => 'Ventas por familia',
        'cash'     => 'Movimientos de caja',
        'tips'     => 'Propinas',
        'taxes'    => 'Modelo 303',
    ];

    public function __construct(
        private ScheduledReportRepositoryInterface $repository,
        private ReportFileGeneratorInterface $fileGenerator,
    ) {}

    public function __invoke(SendScheduledReportNowCommand $command): SendScheduledReportNowResponse
    {
        $report = $this->repository->findByUuid($command->restaurantId, $command->uuid)
            ?? throw ScheduledReportNotFoundException::withUuid($command->uuid);

        $range = DateRange::fromFrequency($report['frequency']);

        $result = $this->fileGenerator->generate(
            restaurantId: $command->restaurantId,
            type:         $report['report_type'],
            format:       $report['format'],
            range:        $range,
        );

        $reportName = self::TYPE_LABELS[$report['report_type']] ?? $report['report_type'];
        $subject = "{$reportName} — {$range->label}";

        $mail = new ScheduledReportMail(
            recipients:    $report['recipients'],
            mailSubject:   $subject,
            reportName:    $reportName,
            periodLabel:   $range->label,
            fileContents:  $result['contents'],
            filename:      $result['filename'],
            mimeType:      $result['mimeType'],
        );

        foreach ($report['recipients'] as $recipient) {
            Mail::to($recipient)->send($mail);
        }

        return SendScheduledReportNowResponse::create(
            uuid:       $report['uuid'],
            reportName: $reportName,
        );
    }
}
