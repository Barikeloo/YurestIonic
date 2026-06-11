<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetProductsReport\GetProductsReport;
use App\Reporting\Application\GetProductsReport\GetProductsReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetProductsReportRequest;
use App\Reporting\Infrastructure\Entrypoint\Http\Support\ReportExportRecorder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final readonly class GetProductsPdfController
{
    public function __construct(
        private GetProductsReport $useCase,
        private ReportExportRecorder $recorder,
    ) {}

    public function __invoke(GetProductsReportRequest $request)
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetProductsReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
            ));

            $data = $response->toArray();

            $pdf = Pdf::loadView('pdf.products', [
                'restaurant'    => $data['restaurant'],
                'periodLabel'   => $data['period_label'],
                'periodRevenue' => $data['period_revenue'],
                'items'         => $data['items'],
                'byZone'        => $data['by_zone'],
                'stockCritical' => $data['stock_critical'],
                'generatedAt'   => (new \DateTimeImmutable('now'))->format('d/m/Y H:i'),
            ]);

            $pdf->setPaper('a4', 'portrait');

            $slug     = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($data['period_label']));
            $filename = 'ventas-por-producto-' . trim((string) $slug, '-') . '.pdf';
            $content  = $pdf->output();

            $this->recorder->record($request, $restaurantId, 'products', 'Ventas por producto · ' . $data['period_label'], 'PDF', $filename, $content);

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
