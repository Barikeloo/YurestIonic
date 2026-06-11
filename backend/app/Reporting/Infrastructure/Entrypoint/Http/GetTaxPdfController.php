<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetTaxReport\GetTaxReport;
use App\Reporting\Application\GetTaxReport\GetTaxReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetTaxReportRequest;
use App\Reporting\Infrastructure\Entrypoint\Http\Support\ReportExportRecorder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final readonly class GetTaxPdfController
{
    public function __construct(
        private GetTaxReport $useCase,
        private ReportExportRecorder $recorder,
    ) {}

    public function __invoke(GetTaxReportRequest $request)
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetTaxReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
                quarter:      $request->validatedQuarter(),
            ));

            $data = $response->toArray();
            $q    = $data['quarterly'];
            $rest = $data['restaurant'];
            $rates = $q['rates'];
            $totalBase = array_sum(array_column($rates, 'base'));
            $totalTax  = array_sum(array_column($rates, 'tax'));

            $pdf = Pdf::loadView('pdf.modelo303', [
                'legalName'    => $rest['legal_name'],
                'businessName' => $rest['name'],
                'taxId'        => $rest['tax_id'],
                'period'       => $q['period'],
                'rates'        => $rates,
                'totalBase'    => $totalBase,
                'totalTax'     => $totalTax,
            ]);

            $pdf->setPaper('a4', 'portrait');

            $filename = 'modelo303-' . $q['period'] . '.pdf';
            $content  = $pdf->output();

            $this->recorder->record($request, $restaurantId, 'taxes', 'Modelo 303 · ' . $q['period'], 'PDF', $filename, $content);

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
