<?php

declare(strict_types=1);

namespace App\Reporting\Application\DispatchDueScheduledReports;

use App\Mail\ScheduledReportMail;
use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Application\Shared\NextRunCalculator;
use App\Reporting\Application\Shared\ReportFileGeneratorInterface;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final readonly class DispatchDueScheduledReports
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

    public function __invoke(\DateTimeImmutable $now): int
    {
        $due = $this->repository->listDue($now);

        $sent = 0;

        foreach ($due as $report) {
            try {
                $range = DateRange::fromFrequency($report['frequency']);

                $result = $this->fileGenerator->generate(
                    restaurantId: $report['restaurant_id'],
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

                $calculator = new NextRunCalculator($now);

                $nextRunAt = match ($report['frequency']) {
                    'daily'     => $calculator->forDaily($report['time']),
                    'weekly'    => $calculator->forWeekly($report['weekday'], $report['time']),
                    'monthly'   => $calculator->forMonthly($report['day_of_month'], $report['time']),
                    'quarterly' => $calculator->forQuarterly($report['time']),
                };

                $this->repository->markRun(
                    uuid:    $report['uuid'],
                    lastRun: $now,
                    nextRun: $nextRunAt,
                );

                $sent++;
            } catch (\Throwable $e) {
                Log::error("Failed to dispatch scheduled report {$report['uuid']}: {$e->getMessage()}", [
                    'uuid'  => $report['uuid'],
                    'error' => $e,
                ]);
            }
        }

        return $sent;
    }
}
