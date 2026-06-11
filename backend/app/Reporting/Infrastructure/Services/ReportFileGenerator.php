<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Services;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Application\Shared\ReportFileGeneratorInterface;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;

final readonly class ReportFileGenerator implements ReportFileGeneratorInterface
{
    private const REPORT_TYPES = ['daily', 'products', 'families', 'cash', 'tips', 'taxes'];

    public function __construct(
        private ReportingRepositoryInterface $repository,
    ) {}

    /**
     * @return array{filename: string, mimeType: string, contents: string}
     */
    public function generate(int $restaurantId, string $type, string $format, DateRange $range, ?string $quarter = null, ?int $year = null): array
    {
        if ($format === 'PDF') {
            return $this->generatePdf($restaurantId, $type, $range, $quarter, $year);
        }

        return $this->generateCsv($restaurantId, $type, $range, $quarter, $year);
    }

    /**
     * @return array{filename: string, mimeType: string, contents: string}
     */
    private function generatePdf(int $restaurantId, string $type, DateRange $range, ?string $quarter, ?int $year): array
    {
        $restaurant = $this->repository->getRestaurantInfo($restaurantId);

        $viewData = match ($type) {
            'daily' => $this->dailyPdfData($restaurantId, $range, $restaurant),
            'products' => $this->productsPdfData($restaurantId, $range, $restaurant),
            'families' => $this->familiesPdfData($restaurantId, $range, $restaurant),
            'cash' => $this->cashPdfData($restaurantId, $range, $restaurant),
            'tips' => $this->tipsPdfData($restaurantId, $range, $restaurant),
            'taxes' => $this->taxesPdfData($restaurantId, $range, $quarter, $year, $restaurant),
        };

        $viewName = match ($type) {
            'daily' => 'pdf.daily',
            'products' => 'pdf.products',
            'families' => 'pdf.families',
            'cash' => 'pdf.cash',
            'tips' => 'pdf.tips',
            'taxes' => 'pdf.modelo303',
        };

        $pdf = Pdf::loadView($viewName, $viewData);
        $pdf->setPaper('a4', 'portrait');

        $filename = $viewData['filename'];
        $contents = (string) $pdf->output();

        return [
            'filename' => $filename,
            'mimeType' => 'application/pdf',
            'contents' => $contents,
        ];
    }

    private function dailyPdfData(int $restaurantId, DateRange $range, array $restaurant): array
    {
        $data = $this->repository->getDashboardData($restaurantId, $range);

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($range->label)), '-');

        return [
            'restaurant'  => $restaurant,
            'periodLabel' => $range->label,
            'kpis'        => $this->buildDailyKpis($data),
            'byFamily'    => $data['by_family'] ?? [],
            'topProducts' => $data['top_products'] ?? [],
            'byPayment'   => $data['by_payment_method'] ?? [],
            'filename'    => 'resumen-diario-' . $slug . '.pdf',
        ];
    }

    private function productsPdfData(int $restaurantId, DateRange $range, array $restaurant): array
    {
        $result = $this->repository->getProductsReport($restaurantId, $range);

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($range->label)), '-');

        return [
            'restaurant'  => $restaurant,
            'periodLabel' => $range->label,
            'items'       => $result['items'] ?? [],
            'periodRevenue' => $result['period_revenue'] ?? 0,
            'filename'    => 'ventas-por-producto-' . $slug . '.pdf',
        ];
    }

    private function familiesPdfData(int $restaurantId, DateRange $range, array $restaurant): array
    {
        $result = $this->repository->getFamiliesReport($restaurantId, $range);
        $families = $result['families'] ?? [];
        $total = array_sum(array_column($families, 'revenue'));

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($range->label)), '-');

        return [
            'restaurant'  => $restaurant,
            'periodLabel' => $range->label,
            'families'    => $families,
            'total'       => $total,
            'filename'    => 'ventas-por-familia-' . $slug . '.pdf',
        ];
    }

    private function cashPdfData(int $restaurantId, DateRange $range, array $restaurant): array
    {
        $data = $this->repository->getCashReport($restaurantId, $range);

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($range->label)), '-');

        return [
            'restaurant'       => $restaurant,
            'periodLabel'      => $range->label,
            'sessions'         => $data['sessions'] ?? [],
            'movements'        => $data['movements'] ?? [],
            'totalIn'          => (int) ($data['total_in'] ?? 0),
            'totalOut'         => (int) ($data['total_out'] ?? 0),
            'net'              => (int) ($data['net'] ?? 0),
            'discrepancyTotal' => (int) ($data['discrepancy_total'] ?? 0),
            'filename'         => 'movimientos-caja-' . $slug . '.pdf',
        ];
    }

    private function tipsPdfData(int $restaurantId, DateRange $range, array $restaurant): array
    {
        $result = $this->repository->getEmployeesReport($restaurantId, $range);

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($range->label)), '-');

        return [
            'restaurant'  => $restaurant,
            'periodLabel' => $range->label,
            'items'       => $result['items'] ?? [],
            'filename'    => 'propinas-' . $slug . '.pdf',
        ];
    }

    private function taxesPdfData(int $restaurantId, DateRange $range, ?string $quarter, ?int $year, array $restaurant): array
    {
        $q = $quarter ?? DateRange::currentQuarter();
        $y = $year ?? (int) (new \DateTimeImmutable('now'))->format('Y');
        $qRange = DateRange::forQuarter($y, $q);

        $result = $this->repository->getTaxReport($restaurantId, $range, $qRange, $q, $y);

        $rates = $result['rates'] ?? [];
        $totalBase = array_sum(array_column($rates, 'base'));
        $totalTax = array_sum(array_column($rates, 'tax'));

        return [
            'restaurant'    => $restaurant,
            'period'        => "{$q} {$y}",
            'rates'         => $rates,
            'totalBase'     => $totalBase,
            'totalTax'      => $totalTax,
            'legalName'     => $restaurant['legal_name'] ?? '',
            'businessName'  => $restaurant['name'] ?? '',
            'taxId'         => $restaurant['tax_id'] ?? '',
            'filename'      => 'modelo303-' . $q . '-' . $y . '.pdf',
        ];
    }

    /**
     * @return array{filename: string, mimeType: string, contents: string}
     */
    private function generateCsv(int $restaurantId, string $type, DateRange $range, ?string $quarter, ?int $year): array
    {
        $baseName = match ($type) {
            'daily'    => 'resumen-diario',
            'products' => 'ventas-por-producto',
            'families' => 'ventas-por-familia',
            'cash'     => 'movimientos-caja',
            'taxes'    => 'desglose-impuestos',
            'tips'     => 'propinas',
        };

        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($range->label)), '-');

        $rows = $this->buildCsvData($type, $restaurantId, $range, $quarter, $year);
        $csv = $this->buildCsvString($rows);

        return [
            'filename' => "{$baseName}-{$slug}.csv",
            'mimeType' => 'text/csv; charset=utf-8',
            'contents' => $csv,
        ];
    }

    private function buildCsvData(string $type, int $restaurantId, DateRange $range, ?string $quarter, ?int $year): array
    {
        return match ($type) {
            'daily'    => $this->buildCsvDaily($restaurantId, $range),
            'products' => $this->buildCsvProducts($restaurantId, $range),
            'families' => $this->buildCsvFamilies($restaurantId, $range),
            'cash'     => $this->buildCsvCash($restaurantId, $range),
            'taxes'    => $this->buildCsvTaxes($restaurantId, $range, $quarter, $year),
            'tips'     => $this->buildCsvTips($restaurantId, $range),
        };
    }

    private function buildCsvDaily(int $restaurantId, DateRange $range): array
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

    private function buildCsvProducts(int $restaurantId, DateRange $range): array
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

    private function buildCsvFamilies(int $restaurantId, DateRange $range): array
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

    private function buildCsvCash(int $restaurantId, DateRange $range): array
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

    private function buildCsvTaxes(int $restaurantId, DateRange $range, ?string $quarter, ?int $year): array
    {
        $q = $quarter ?? DateRange::currentQuarter();
        $y = $year ?? (int) (new \DateTimeImmutable('now'))->format('Y');
        $qRange = DateRange::forQuarter($y, $q);

        $report = $this->repository->getTaxReport($restaurantId, $range, $qRange, $q, $y);

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
        $rows[] = [
            'TOTAL',
            number_format($totalBase / 100, 2, ',', ''),
            number_format($totalTax / 100, 2, ',', ''),
            number_format(($totalBase + $totalTax) / 100, 2, ',', ''),
        ];

        $rows[] = [];
        $rows[] = ['Propinas tarjeta', number_format(($report['tips_card'] ?? 0) / 100, 2, ',', '')];

        return $rows;
    }

    private function buildCsvTips(int $restaurantId, DateRange $range): array
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

    private function buildCsvString(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function buildDailyKpis(array $data): array
    {
        $revenue   = (int) ($data['revenue'] ?? 0);
        $tickets   = (int) ($data['tickets'] ?? 0);
        $avgTicket = $tickets > 0 ? intdiv($revenue, $tickets) : 0;
        $itemsSold = (int) ($data['items_sold'] ?? 0);
        $diners    = (int) ($data['diners'] ?? 0);

        $prevRevenue   = (int) ($data['prev_revenue'] ?? 0);
        $prevTickets   = (int) ($data['prev_tickets'] ?? 0);
        $prevAvgTicket = $prevTickets > 0 ? intdiv($prevRevenue, $prevTickets) : 0;
        $prevItemsSold = (int) ($data['prev_items_sold'] ?? 0);
        $prevDiners    = (int) ($data['prev_diners'] ?? 0);

        return [
            'revenue'    => $this->metric($revenue, $prevRevenue),
            'tickets'    => $this->metric($tickets, $prevTickets),
            'avg_ticket' => $this->metric($avgTicket, $prevAvgTicket),
            'items_sold' => $this->metric($itemsSold, $prevItemsSold),
            'diners'     => $this->metric($diners, $prevDiners),
        ];
    }

    private function metric(int $v, int $prev): array
    {
        $deltaPct = $prev > 0 ? round(($v - $prev) / $prev * 100, 1) : 0.0;

        return ['v' => $v, 'prev' => $prev, 'delta_pct' => $deltaPct];
    }
}
