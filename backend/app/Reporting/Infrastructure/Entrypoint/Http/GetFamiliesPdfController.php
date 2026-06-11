<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetFamiliesReport\GetFamiliesReport;
use App\Reporting\Application\GetFamiliesReport\GetFamiliesReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetFamiliesReportRequest;
use App\Reporting\Infrastructure\Entrypoint\Http\Support\ReportExportRecorder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final readonly class GetFamiliesPdfController
{
    public function __construct(
        private GetFamiliesReport $useCase,
        private ReportExportRecorder $recorder,
    ) {}

    public function __invoke(GetFamiliesReportRequest $request)
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetFamiliesReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
            ));

            $data = $response->toArray();

            $pdf = Pdf::loadView('pdf.families', [
                'restaurant'  => $data['restaurant'],
                'periodLabel' => $data['period_label'],
                'families'    => $data['families'],
                'total'       => $data['total'],
                'prevTotal'   => $data['prev_total'],
                'generatedAt' => (new \DateTimeImmutable('now'))->format('d/m/Y H:i'),
            ]);

            $pdf->setPaper('a4', 'portrait');

            $slug     = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($data['period_label']));
            $filename = 'ventas-por-familia-' . trim((string) $slug, '-') . '.pdf';
            $content  = $pdf->output();

            $this->recorder->record($request, $restaurantId, 'families', 'Ventas por familia · ' . $data['period_label'], 'PDF', $filename, $content);

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
