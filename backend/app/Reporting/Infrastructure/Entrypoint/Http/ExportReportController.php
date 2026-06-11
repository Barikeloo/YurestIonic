<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;
use App\Reporting\Infrastructure\Entrypoint\Http\Support\ReportExportRecorder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class ExportReportController
{
    private const VALID_TYPES = ['daily', 'products', 'families', 'cash', 'taxes', 'tips'];
    private const VALID_PERIODS = ['today', 'yesterday', 'week', 'month'];

    public function __construct(
        private ReportingRepositoryInterface $repository,
        private ReportExportRecorder $recorder,
    ) {}

    public function __invoke(Request $request, string $type): Response|JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        if (!in_array($type, self::VALID_TYPES, true)) {
            return response()->json(['error' => 'Invalid report type'], 422);
        }

        $period = (string) $request->input('period', 'today');

        if (!in_array($period, self::VALID_PERIODS, true)) {
            return response()->json(['error' => 'Invalid period'], 422);
        }

        $range = DateRange::fromPeriod($period);
        $year  = (int) (new \DateTimeImmutable('now'))->format('Y');

        $baseName = match ($type) {
            'daily'    => 'resumen-diario',
            'products' => 'ventas-por-producto',
            'families' => 'ventas-por-familia',
            'cash'     => 'movimientos-caja',
            'taxes'    => 'desglose-impuestos',
            'tips'     => 'propinas',
        };

        $title = match ($type) {
            'daily'    => 'Resumen diario',
            'products' => 'Ventas por producto',
            'families' => 'Ventas por familia',
            'cash'     => 'Movimientos de caja',
            'taxes'    => 'Desglose de impuestos',
            'tips'     => 'Propinas',
        };

        $slug     = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($range->label)), '-');
        $filename = "{$baseName}-{$slug}.csv";

        $data = $this->buildData($type, $restaurantId, $range, $period, $year);
        $csv  = $this->buildCsv($data);

        $this->recorder->record($request, $restaurantId, $type, "{$title} · {$range->label}", 'CSV', $filename, $csv);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildData(string $type, int $restaurantId, DateRange $range, string $period, int $year): array
    {
        return match ($type) {
            'daily'    => $this->buildDaily($restaurantId, $range),
            'products' => $this->buildProducts($restaurantId, $range),
            'families' => $this->buildFamilies($restaurantId, $range),
            'cash'     => $this->buildCash($restaurantId, $range),
            'taxes'    => $this->buildTaxes($restaurantId, $range, $period, $year),
            'tips'     => $this->buildTips($restaurantId, $range),
        };
    }

    private function buildDaily(int $restaurantId, DateRange $range): array
    {
        $data = $this->repository->getDashboardData($restaurantId, $range);

        $revenue     = (int) ($data['revenue'] ?? 0);
        $tickets     = (int) ($data['tickets'] ?? 0);
        $avgTicket   = $tickets > 0 ? intdiv($revenue, $tickets) : 0;
        $itemsSold   = (int) ($data['items_sold'] ?? 0);
        $diners      = (int) ($data['diners'] ?? 0);
        $prevRevenue = (int) ($data['prev_revenue'] ?? 0);
        $prevTickets = (int) ($data['prev_tickets'] ?? 0);
        $prevItems   = (int) ($data['prev_items_sold'] ?? 0);
        $prevDiners  = (int) ($data['prev_diners'] ?? 0);

        $delta = fn(int $v, int $p): float => $p > 0 ? round(($v - $p) / $p * 100, 1) : 0.0;

        $rows = [];
        $rows[] = ['Métrica', 'Valor', 'vs periodo anterior'];
        $rows[] = ['Ingresos',              number_format($revenue / 100, 2, ',', ''), $delta($revenue, $prevRevenue) . '%'];
        $rows[] = ['Tickets',               $tickets,                                  $delta($tickets, $prevTickets) . '%'];
        $rows[] = ['Ticket medio',          number_format($avgTicket / 100, 2, ',', ''), '—'];
        $rows[] = ['Artículos vendidos',    $itemsSold,                                 $delta($itemsSold, $prevItems) . '%'];
        $rows[] = ['Comensales',            $diners,                                    $delta($diners, $prevDiners) . '%'];

        $rows[] = [];
        $rows[] = ['Desglose por método de pago'];

        $methods = $data['by_payment_method'] ?? [];
        foreach ($methods as $method => $amount) {
            $rows[] = [ucfirst($method), number_format((int) $amount / 100, 2, ',', '')];
        }

        return $rows;
    }

    private function buildProducts(int $restaurantId, DateRange $range): array
    {
        $report = $this->repository->getProductsReport($restaurantId, $range);
        $items = $report['items'] ?? [];

        $rows = [];
        $rows[] = ['Producto', 'Familia', 'Unidades', 'Ingresos (€)', '% sobre total'];

        $total = array_sum(array_column($items, 'revenue'));

        foreach ($items as $item) {
            $pct = $total > 0 ? round(($item['revenue'] / $total) * 100, 1) : 0;
            $rows[] = [
                $item['name'],
                $item['family'] ?? '—',
                $item['units'],
                number_format($item['revenue'] / 100, 2, ',', ''),
                $pct . '%',
            ];
        }

        return $rows;
    }

    private function buildFamilies(int $restaurantId, DateRange $range): array
    {
        $dashboard = $this->repository->getDashboardData($restaurantId, $range);
        $families = $dashboard['by_family'] ?? [];

        $rows = [];
        $rows[] = ['Familia', 'Ingresos (€)', '% sobre total'];

        $total = array_sum(array_column($families, 'v'));

        foreach ($families as $f) {
            $pct = $total > 0 ? round(($f['v'] / $total) * 100, 1) : 0;
            $rows[] = [
                $f['label'],
                number_format($f['v'] / 100, 2, ',', ''),
                $pct . '%',
            ];
        }

        return $rows;
    }

    private function buildCash(int $restaurantId, DateRange $range): array
    {
        $data = $this->repository->getCashReport($restaurantId, $range);

        $eur = static fn ($cents): string => number_format((int) $cents / 100, 2, ',', '');
        $dt  = static fn (?string $s): string => $s ? date('d/m/Y H:i', (int) strtotime($s)) : '—';

        $reasons = [
            'change_refill'    => 'Recambio de cambio',
            'supplier_payment' => 'Pago a proveedor',
            'tip_declared'     => 'Propina declarada',
            'sangria'          => 'Sangría',
            'adjustment'       => 'Ajuste',
            'cancellation'     => 'Cancelación',
            'other'            => 'Otros',
        ];

        $rows = [];
        $rows[] = ['Sesiones cerradas'];
        $rows[] = ['Cierre', 'Apertura', 'Cerrada por', 'Fondo inicial (€)', 'Esperado (€)', 'Contado (€)', 'Descuadre (€)', 'Motivo', 'Z'];

        foreach ($data['sessions'] ?? [] as $s) {
            $rows[] = [
                $dt($s['closed_at'] ?? null),
                $dt($s['opened_at'] ?? null),
                $s['closed_by'] ?? '—',
                $eur($s['initial_amount'] ?? 0),
                $eur($s['expected_amount'] ?? 0),
                $eur($s['final_amount'] ?? 0),
                $eur($s['discrepancy'] ?? 0),
                $s['discrepancy_reason'] ?? '',
                $s['z_report_number'] ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = ['Movimientos manuales de efectivo'];
        $rows[] = ['Fecha', 'Tipo', 'Motivo', 'Importe (€)', 'Usuario', 'Descripción'];

        foreach ($data['movements'] ?? [] as $m) {
            $isIn = ($m['type'] ?? '') === 'in';
            $rows[] = [
                $dt($m['created_at'] ?? null),
                $isIn ? 'Entrada' : 'Salida',
                $reasons[$m['reason_code'] ?? 'other'] ?? 'Otros',
                ($isIn ? '' : '-') . $eur($m['amount'] ?? 0),
                $m['user_name'] ?? '—',
                $m['description'] ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = ['Total entradas (€)', $eur($data['total_in'] ?? 0)];
        $rows[] = ['Total salidas (€)', $eur($data['total_out'] ?? 0)];
        $rows[] = ['Neto (€)', $eur($data['net'] ?? 0)];
        $rows[] = ['Descuadre acumulado (€)', $eur($data['discrepancy_total'] ?? 0)];

        return $rows;
    }

    private function buildTaxes(int $restaurantId, DateRange $range, string $period, int $year): array
    {
        $currentQ = DateRange::currentQuarter();
        $qRange = DateRange::forQuarter($year, $currentQ);
        $report = $this->repository->getTaxReport($restaurantId, $range, $qRange, $currentQ, $year);

        $rows = [];
        $rows[] = ['Tramo', 'Base imponible (€)', 'Cuota IVA (€)', 'Total (€)'];

        foreach ($report['breakdown'] ?? [] as $b) {
            $rows[] = [
                $b['label'] . ' (' . $b['rate'] . '%)',
                number_format($b['base'] / 100, 2, ',', ''),
                number_format($b['tax'] / 100, 2, ',', ''),
                number_format($b['total'] / 100, 2, ',', ''),
            ];
        }

        $rows[] = [];
        $totalBase = array_sum(array_column($report['breakdown'] ?? [], 'base'));
        $totalTax  = array_sum(array_column($report['breakdown'] ?? [], 'tax'));
        $rows[] = ['TOTAL', number_format($totalBase / 100, 2, ',', ''), number_format($totalTax / 100, 2, ',', ''), number_format(($totalBase + $totalTax) / 100, 2, ',', '')];

        $rows[] = [];
        $rows[] = ['Propinas tarjeta', number_format(($report['tips_card'] ?? 0) / 100, 2, ',', '')];

        return $rows;
    }

    private function buildTips(int $restaurantId, DateRange $range): array
    {
        $report = $this->repository->getEmployeesReport($restaurantId, $range);
        $items = $report['items'] ?? [];

        $rows = [];
        $rows[] = ['Empleado', 'Tickets', 'Ingresos (€)', 'Propinas (€)'];

        foreach ($items as $item) {
            $rows[] = [
                $item['name'] ?? '—',
                $item['tickets'] ?? 0,
                number_format(($item['revenue'] ?? 0) / 100, 2, ',', ''),
                number_format(($item['tips'] ?? 0) / 100, 2, ',', ''),
            ];
        }

        return $rows;
    }

    private function buildCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel compat

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }
}
