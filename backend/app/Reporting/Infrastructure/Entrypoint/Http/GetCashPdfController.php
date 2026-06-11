<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetCashReport\GetCashReport;
use App\Reporting\Application\GetCashReport\GetCashReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetCashReportRequest;
use App\Reporting\Infrastructure\Entrypoint\Http\Support\ReportExportRecorder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final readonly class GetCashPdfController
{
    public function __construct(
        private GetCashReport $useCase,
        private ReportExportRecorder $recorder,
    ) {}

    public function __invoke(GetCashReportRequest $request)
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetCashReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
            ));

            $data = $response->toArray();

            $pdf = Pdf::loadView('pdf.cash', [
                'restaurant'       => $data['restaurant'],
                'periodLabel'      => $data['period_label'],
                'sessions'         => $data['sessions'],
                'movements'        => $data['movements'],
                'totalIn'          => $data['total_in'],
                'totalOut'         => $data['total_out'],
                'net'              => $data['net'],
                'discrepancyTotal' => $data['discrepancy_total'],
                'generatedAt'      => (new \DateTimeImmutable('now'))->format('d/m/Y H:i'),
            ]);

            $pdf->setPaper('a4', 'portrait');

            $slug     = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($data['period_label']));
            $filename = 'movimientos-caja-' . trim((string) $slug, '-') . '.pdf';
            $content  = $pdf->output();

            $this->recorder->record($request, $restaurantId, 'cash', 'Movimientos de caja · ' . $data['period_label'], 'PDF', $filename, $content);

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
