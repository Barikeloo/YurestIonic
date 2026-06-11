<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetEmployeesReport\GetEmployeesReport;
use App\Reporting\Application\GetEmployeesReport\GetEmployeesReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetEmployeesReportRequest;
use App\Reporting\Infrastructure\Entrypoint\Http\Support\ReportExportRecorder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final readonly class GetTipsPdfController
{
    public function __construct(
        private GetEmployeesReport $useCase,
        private ReportExportRecorder $recorder,
    ) {}

    public function __invoke(GetEmployeesReportRequest $request)
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetEmployeesReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
            ));

            $data = $response->toArray();

            // Only employees who actually declared tips, sorted by tips desc.
            $employees = array_values(array_filter($data['items'], fn ($e) => (int) ($e['tips'] ?? 0) > 0));
            usort($employees, fn ($a, $b) => ($b['tips'] ?? 0) <=> ($a['tips'] ?? 0));

            $pdf = Pdf::loadView('pdf.tips', [
                'restaurant'  => $data['restaurant'],
                'periodLabel' => $data['period_label'],
                'employees'   => $employees,
                'totalTips'   => array_sum(array_column($employees, 'tips')),
                'generatedAt' => (new \DateTimeImmutable('now'))->format('d/m/Y H:i'),
            ]);

            $pdf->setPaper('a4', 'portrait');

            $slug     = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($data['period_label']));
            $filename = 'propinas-' . trim((string) $slug, '-') . '.pdf';
            $content  = $pdf->output();

            $this->recorder->record($request, $restaurantId, 'tips', 'Propinas declaradas · ' . $data['period_label'], 'PDF', $filename, $content);

            return response($content, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
