<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetDailyReport\GetDailyReport;
use App\Reporting\Application\GetDailyReport\GetDailyReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetDailyReportRequest;
use App\Reporting\Infrastructure\Entrypoint\Http\Support\ReportExportRecorder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final readonly class GetDailyPdfController
{
    public function __construct(
        private GetDailyReport $useCase,
        private ReportExportRecorder $recorder,
    ) {}

    public function __invoke(GetDailyReportRequest $request)
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetDailyReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
            ));

            $data = $response->toArray();

            $pdf = Pdf::loadView('pdf.daily', [
                'restaurant'  => $data['restaurant'],
                'periodLabel' => $data['period_label'],
                'kpis'        => $data['kpis'],
                'byFamily'    => $data['by_family'],
                'topProducts' => $data['top_products'],
                'byPayment'   => $data['by_payment_method'],
                'generatedAt' => (new \DateTimeImmutable('now'))->format('d/m/Y H:i'),
            ]);

            $pdf->setPaper('a4', 'portrait');

            $slug     = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($data['period_label']));
            $filename = 'resumen-diario-' . trim((string) $slug, '-') . '.pdf';
            $content  = $pdf->output();

            $this->recorder->record($request, $restaurantId, 'daily', 'Resumen diario · ' . $data['period_label'], 'PDF', $filename, $content);

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
