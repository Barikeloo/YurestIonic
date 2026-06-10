<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Persistence;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentReportingRepository implements ReportingRepositoryInterface
{
    public function getDashboardData(int $restaurantId, DateRange $range): array
    {
        $from     = $range->from->format('Y-m-d H:i:s');
        $to       = $range->to->format('Y-m-d H:i:s');
        $prevFrom = $range->prevFrom->format('Y-m-d H:i:s');
        $prevTo   = $range->prevTo->format('Y-m-d H:i:s');

        $kpis = $this->fetchKpis($restaurantId, $from, $to);
        $prevKpis = $this->fetchKpis($restaurantId, $prevFrom, $prevTo);

        $itemsSold     = $this->fetchItemsSold($restaurantId, $from, $to);
        $prevItemsSold = $this->fetchItemsSold($restaurantId, $prevFrom, $prevTo);

        $diners     = $this->fetchDiners($restaurantId, $from, $to);
        $prevDiners = $this->fetchDiners($restaurantId, $prevFrom, $prevTo);

        [$byHour, $byHourPrev] = [
            $this->fetchByHour($restaurantId, $from, $to),
            $this->fetchByHour($restaurantId, $prevFrom, $prevTo),
        ];

        $sparkDate   = now()->subDays(13)->toDateString();
        $dayTotals   = $this->fetchDayTotals($restaurantId, $sparkDate);
        $dayItems    = $this->fetchDayItems($restaurantId, $sparkDate);
        $byFamily    = $this->fetchByFamily($restaurantId, $from, $to);
        $topProducts = $this->fetchTopProducts($restaurantId, $from, $to);
        $byPayment   = $this->fetchByPaymentMethod($restaurantId, $from, $to);

        return [
            'revenue'           => (int) $kpis->revenue,
            'tickets'           => (int) $kpis->tickets,
            'items_sold'        => $itemsSold,
            'diners'            => $diners,
            'prev_revenue'      => (int) $prevKpis->revenue,
            'prev_tickets'      => (int) $prevKpis->tickets,
            'prev_items_sold'   => $prevItemsSold,
            'prev_diners'       => $prevDiners,
            'by_hour'           => $byHour,
            'by_hour_prev'      => $byHourPrev,
            'day_totals'        => $dayTotals,
            'day_items'         => $dayItems,
            'by_family'         => $byFamily,
            'top_products'      => $topProducts,
            'by_payment_method' => $byPayment,
        ];
    }

    private function fetchKpis(int $restaurantId, string $from, string $to): object
    {
        return DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'closed')
            ->whereBetween('value_date', [$from, $to])
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue, COUNT(*) as tickets')
            ->first();
    }

    private function fetchItemsSold(int $restaurantId, string $from, string $to): int
    {
        return (int) DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->whereNotNull('sl.product_id')
            ->sum('sl.quantity');
    }

    private function fetchDiners(int $restaurantId, string $from, string $to): int
    {
        return (int) DB::table('orders as o')
            ->join('sales as s', 's.order_id', '=', 'o.id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('o.deleted_at')
            ->groupBy('o.id')
            ->selectRaw('MAX(o.diners) as d')
            ->get()
            ->sum('d');
    }

    private function fetchByHour(int $restaurantId, string $from, string $to): array
    {
        return DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'closed')
            ->whereBetween('value_date', [$from, $to])
            ->whereNull('deleted_at')
            ->selectRaw('HOUR(value_date) as h, SUM(total) as v, COUNT(*) as n')
            ->groupByRaw('HOUR(value_date)')
            ->get()
            ->map(fn ($r) => ['h' => (int) $r->h, 'v' => (int) $r->v, 'n' => (int) $r->n])
            ->toArray();
    }

    private function fetchDayTotals(int $restaurantId, string $since): array
    {
        $rows = DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'closed')
            ->whereDate('value_date', '>=', $since)
            ->whereNull('deleted_at')
            ->selectRaw('DATE(value_date) as d, SUM(total) as v, COUNT(*) as n')
            ->groupByRaw('DATE(value_date)')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->d] = ['v' => (int) $row->v, 'n' => (int) $row->n];
        }
        return $result;
    }

    private function fetchDayItems(int $restaurantId, string $since): array
    {
        $rows = DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereDate('s.value_date', '>=', $since)
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->whereNotNull('sl.product_id')
            ->selectRaw('DATE(s.value_date) as d, SUM(sl.quantity) as items')
            ->groupByRaw('DATE(s.value_date)')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->d] = (int) $row->items;
        }
        return $result;
    }

    private function fetchByFamily(int $restaurantId, string $from, string $to): array
    {
        return DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->join('products as p', 'p.id', '=', 'sl.product_id')
            ->join('families as f', 'f.id', '=', 'p.family_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereNull('f.deleted_at')
            ->whereNotNull('sl.product_id')
            ->selectRaw('f.name as label, SUM(sl.quantity * sl.price) as v')
            ->groupBy('f.id', 'f.name')
            ->orderByRaw('SUM(sl.quantity * sl.price) DESC')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'v' => (int) $r->v])
            ->toArray();
    }

    private function fetchTopProducts(int $restaurantId, string $from, string $to): array
    {
        return DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->join('products as p', 'p.id', '=', 'sl.product_id')
            ->join('families as f', 'f.id', '=', 'p.family_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereNull('f.deleted_at')
            ->whereNotNull('sl.product_id')
            ->selectRaw('p.name as name, f.name as family, SUM(sl.quantity) as units, SUM(sl.quantity * sl.price) as revenue')
            ->groupBy('p.id', 'p.name', 'f.id', 'f.name')
            ->orderByRaw('SUM(sl.quantity * sl.price) DESC')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'name'    => $r->name,
                'family'  => $r->family,
                'units'   => (int) $r->units,
                'revenue' => (int) $r->revenue,
            ])
            ->toArray();
    }

    public function getSalesList(int $restaurantId, DateRange $range, int $page, int $perPage): array
    {
        $from = $range->from->format('Y-m-d H:i:s');
        $to   = $range->to->format('Y-m-d H:i:s');

        $baseQuery = DB::table('sales as s')
            ->where('s.restaurant_id', $restaurantId)
            ->whereIn('s.status', ['closed', 'cancelled'])
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at');

        $total    = (clone $baseQuery)->count();
        $lastPage = max((int) ceil($total / $perPage), 1);
        $offset   = ($page - 1) * $perPage;

        $rows = (clone $baseQuery)
            ->leftJoin('orders as o', 'o.id', '=', 's.order_id')
            ->leftJoin('tables as tb', 'tb.id', '=', 'o.table_id')
            ->leftJoin('zones as z', 'z.id', '=', 'tb.zone_id')
            ->leftJoin('users as u', 'u.id', '=', 's.opened_by_user_id')
            ->selectRaw('
                s.uuid, s.ticket_number, s.value_date, s.total, s.status,
                s.cancel_reason, s.document_type,
                o.diners as diners,
                z.name as zone_name,
                tb.name as table_name,
                u.name as opened_by
            ')
            ->orderByDesc('s.value_date')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();

        $saleIds = DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', ['closed', 'cancelled'])
            ->whereBetween('value_date', [$from, $to])
            ->whereNull('deleted_at')
            ->pluck('id');

        $payments   = $this->fetchPaymentsBySaleIds($saleIds);
        $tipsBySale = $this->fetchTipsBySaleIds($saleIds);

        foreach ($rows as &$row) {
            $saleUuid         = $row['uuid'];
            $row['diners']    = (int) ($row['diners'] ?? 0);
            $row['total']     = (int) $row['total'];
            $row['tips_total'] = (int) ($tipsBySale[$saleUuid] ?? 0);
            $row['payment_methods'] = $payments[$saleUuid] ?? [];
        }

        $totals = $this->fetchTotalsForSales($restaurantId, $from, $to);

        return [
            'data'   => $rows,
            'meta'   => [
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => $lastPage,
            ],
            'totals' => $totals,
        ];
    }

    public function getSaleDetail(int $restaurantId, string $saleUuid): ?array
    {
        $sale = DB::table('sales as s')
            ->leftJoin('orders as o', 'o.id', '=', 's.order_id')
            ->leftJoin('tables as tb', 'tb.id', '=', 'o.table_id')
            ->leftJoin('zones as z', 'z.id', '=', 'tb.zone_id')
            ->leftJoin('users as opener', 'opener.id', '=', 's.opened_by_user_id')
            ->leftJoin('users as closer', 'closer.id', '=', 's.closed_by_user_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.uuid', $saleUuid)
            ->selectRaw('
                s.uuid, s.ticket_number, s.value_date, s.total, s.status,
                s.cancel_reason, s.document_type,
                z.name as zone_name, tb.name as table_name,
                opener.name as opened_by, closer.name as closed_by
            ')
            ->first();

        if ($sale === null) {
            return null;
        }

        $saleId = DB::table('sales')->where('uuid', $saleUuid)->where('restaurant_id', $restaurantId)->value('id');

        if ($saleId === null) {
            return null;
        }

        $lines = DB::table('sales_lines as sl')
            ->leftJoin('products as p', 'p.id', '=', 'sl.product_id')
            ->leftJoin('families as f', 'f.id', '=', 'p.family_id')
            ->where('sl.sale_id', $saleId)
            ->whereNull('sl.deleted_at')
            ->selectRaw('
                COALESCE(p.name, "(deleted)") as product_name,
                COALESCE(f.name, "-") as family_name,
                sl.quantity as qty,
                sl.price as unit_price,
                sl.tax_percentage as tax_pct,
                (sl.quantity * sl.price) as total
            ')
            ->get()
            ->map(fn ($r) => [
                'product_name' => $r->product_name,
                'family_name'  => $r->family_name,
                'qty'          => (int) $r->qty,
                'unit_price'   => (int) $r->unit_price,
                'tax_pct'      => (int) $r->tax_pct,
                'total'        => (int) $r->total,
            ])
            ->toArray();

        $payments = DB::table('sale_payments')
            ->where('sale_id', $saleId)
            ->whereNull('deleted_at')
            ->selectRaw('method, SUM(amount_cents) as amount')
            ->groupBy('method')
            ->get()
            ->map(fn ($r) => [
                'method' => $r->method,
                'amount' => (int) $r->amount,
            ])
            ->values()
            ->toArray();

        $tipsTotal = DB::table('sale_payments')
            ->where('sale_id', $saleId)
            ->where('method', 'tip')
            ->whereNull('deleted_at')
            ->sum('amount_cents');

        $taxBreakdown = DB::table('sales_lines')
            ->where('sale_id', $saleId)
            ->whereNull('deleted_at')
            ->selectRaw('
                tax_percentage as rate,
                SUM(price * quantity) as base,
                SUM(price * quantity * tax_percentage / 100) as tax
            ')
            ->groupBy('tax_percentage')
            ->get()
            ->map(fn ($r) => [
                'rate' => (int) $r->rate,
                'base' => (int) $r->base,
                'tax'  => (int) $r->tax,
            ])
            ->toArray();

        $subtotal = array_sum(array_column($taxBreakdown, 'base'));
        $taxTotal = array_sum(array_column($taxBreakdown, 'tax'));

        $duration = null;
        if ($sale->value_date && $sale->closed_by) {
            $opened  = new \DateTimeImmutable($sale->value_date);
            $closed  = new \DateTimeImmutable($sale->value_date);
            $duration = (int) $opened->diff($closed)->format('%i');
        }

        return [
            'uuid'             => $sale->uuid,
            'ticket_number'    => (int) $sale->ticket_number,
            'value_date'       => $sale->value_date,
            'status'           => $sale->status,
            'zone_name'        => $sale->zone_name ?? '—',
            'table_name'       => $sale->table_name ?? '—',
            'diners'           => 0,
            'opened_by'        => $sale->opened_by ?? '—',
            'duration_minutes' => $duration,
            'lines'            => $lines,
            'payments'         => $payments,
            'tax_breakdown'    => $taxBreakdown,
            'subtotal'         => $subtotal,
            'tax_total'        => $taxTotal,
            'tips_total'       => (int) $tipsTotal,
            'cancel_reason'    => $sale->cancel_reason,
        ];
    }

    private function fetchPaymentsBySaleIds($saleIds): array
    {
        $rows = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->whereIn('sale_payments.sale_id', $saleIds)
            ->whereNull('sale_payments.deleted_at')
            ->where('sale_payments.method', '!=', 'tip')
            ->selectRaw('sales.uuid, sale_payments.method, SUM(sale_payments.amount_cents) as amount')
            ->groupBy('sales.uuid', 'sale_payments.method')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->uuid][] = [
                'method' => $row->method,
                'amount' => (int) $row->amount,
            ];
        }
        return $result;
    }

    private function fetchTipsBySaleIds($saleIds): array
    {
        $rows = DB::table('sale_payments')
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->whereIn('sale_payments.sale_id', $saleIds)
            ->whereNull('sale_payments.deleted_at')
            ->where('sale_payments.method', 'tip')
            ->selectRaw('sales.uuid, SUM(sale_payments.amount_cents) as total')
            ->groupBy('sales.uuid')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->uuid] = (int) $row->total;
        }
        return $result;
    }

    private function fetchTotalsForSales(int $restaurantId, string $from, string $to): array
    {
        $totals = DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'closed')
            ->whereBetween('value_date', [$from, $to])
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->first();

        $payments = DB::table('sale_payments as sp')
            ->join('sales as s', 's.id', '=', 'sp.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sp.deleted_at')
            ->where('sp.method', '!=', 'tip')
            ->selectRaw('sp.method, SUM(sp.amount_cents) as amount')
            ->groupBy('sp.method')
            ->get()
            ->keyBy('method');

        $tips = DB::table('sale_payments as sp')
            ->join('sales as s', 's.id', '=', 'sp.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sp.deleted_at')
            ->where('sp.method', 'tip')
            ->sum('sp.amount_cents');

        return [
            'revenue' => (int) ($totals->revenue ?? 0),
            'cash'    => (int) ($payments->get('cash')?->amount       ?? 0),
            'card'    => (int) ($payments->get('card')?->amount       ?? 0),
            'bizum'   => (int) ($payments->get('bizum')?->amount      ?? 0),
            'other'   => (int) ($payments->get('other')?->amount      ?? 0),
            'tips'    => (int) $tips,
        ];
    }

    private function fetchByPaymentMethod(int $restaurantId, string $from, string $to): array
    {
        $rows = DB::table('sale_payments as sp')
            ->join('sales as s', 's.id', '=', 'sp.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->selectRaw('sp.method, SUM(sp.amount_cents) as v')
            ->groupBy('sp.method')
            ->get()
            ->keyBy('method');

        return [
            'cash'       => (int) ($rows->get('cash')?->v       ?? 0),
            'card'       => (int) ($rows->get('card')?->v       ?? 0),
            'bizum'      => (int) ($rows->get('bizum')?->v      ?? 0),
            'voucher'    => (int) ($rows->get('voucher')?->v    ?? 0),
            'invitation' => (int) ($rows->get('invitation')?->v ?? 0),
            'other'      => (int) ($rows->get('other')?->v      ?? 0),
        ];
    }

    public function getHeatmap(int $restaurantId): array
    {
        $since = now()->subWeeks(4)->startOfDay();

        $rows = DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'closed')
            ->where('value_date', '>=', $since)
            ->whereNull('deleted_at')
            ->selectRaw('
                DAYOFWEEK(value_date) as dow,
                HOUR(value_date) as hr,
                SUM(total) as v,
                COUNT(*) as n
            ')
            ->groupByRaw('DAYOFWEEK(value_date), HOUR(value_date)')
            ->get();

        $dayNames = [1 => 'Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $hours    = ['08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'];
        $grouped  = [];

        foreach ($rows as $r) {
            $grouped[(int) $r->dow][(int) $r->hr] = ['v' => (int) $r->v, 'n' => (int) $r->n];
        }

        $result = [];
        foreach ($dayNames as $dow => $label) {
            if ($dow === 1) continue; // skip Sunday as index 1 — start with Monday

            $cells = [];
            foreach ($hours as $h) {
                $cell   = $grouped[$dow][(int) $h] ?? null;
                $cells[] = [
                    'h' => $h,
                    'v' => $cell['v'] ?? 0,
                    'n' => $cell['n'] ?? 0,
                ];
            }
            $result[] = ['day' => $label, 'hours' => $cells];
        }

        // Add Sunday at the end
        if (isset($dayNames[1])) {
            $cells = [];
            foreach ($hours as $h) {
                $cell   = $grouped[1][(int) $h] ?? null;
                $cells[] = [
                    'h' => $h,
                    'v' => $cell['v'] ?? 0,
                    'n' => $cell['n'] ?? 0,
                ];
            }
            $result[] = ['day' => 'Dom', 'hours' => $cells];
        }

        return $result;
    }

    public function getProductsReport(int $restaurantId, DateRange $range): array
    {
        $from = $range->from->format('Y-m-d H:i:s');
        $to   = $range->to->format('Y-m-d H:i:s');

        $periodRevenue = (int) DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'closed')
            ->whereBetween('value_date', [$from, $to])
            ->whereNull('deleted_at')
            ->sum('total');

        $stockCritical = DB::table('products as p')
            ->join('families as f', 'f.id', '=', 'p.family_id')
            ->where('f.restaurant_id', $restaurantId)
            ->where('p.active', 1)
            ->where('p.stock', '<=', 10)
            ->whereNull('p.deleted_at')
            ->whereNull('f.deleted_at')
            ->orderBy('p.stock', 'ASC')
            ->selectRaw('p.name, p.stock, f.name as family')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'family' => $r->family, 'stock' => (int) $r->stock])
            ->toArray();

        $sevenDaysAgo = now()->subDays(7)->toDateString();
        $noSales7d = DB::table('products as p')
            ->join('families as f', 'f.id', '=', 'p.family_id')
            ->where('f.restaurant_id', $restaurantId)
            ->where('p.active', 1)
            ->whereNull('p.deleted_at')
            ->whereNull('f.deleted_at')
            ->whereNotIn('p.id', function ($q) use ($restaurantId, $sevenDaysAgo) {
                $q->select('sl.product_id')
                    ->from('sales_lines as sl')
                    ->join('sales as s', 's.id', '=', 'sl.sale_id')
                    ->where('s.restaurant_id', $restaurantId)
                    ->where('s.status', 'closed')
                    ->whereDate('s.value_date', '>=', $sevenDaysAgo)
                    ->whereNull('s.deleted_at')
                    ->whereNull('sl.deleted_at')
                    ->whereNotNull('sl.product_id');
            })
            ->orderBy('p.name', 'ASC')
            ->selectRaw('p.name, p.stock, f.name as family')
            ->limit(8)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'family' => $r->family, 'stock' => (int) $r->stock])
            ->toArray();

        $alertCount = DB::table('products as p')
            ->join('families as f', 'f.id', '=', 'p.family_id')
            ->where('f.restaurant_id', $restaurantId)
            ->where('p.active', 1)
            ->where('p.stock', '<=', 5)
            ->whereNull('p.deleted_at')
            ->whereNull('f.deleted_at')
            ->count();

        $byZone = DB::table('sales as s')
            ->join('orders as o', 'o.id', '=', 's.order_id')
            ->join('tables as tb', 'tb.id', '=', 'o.table_id')
            ->join('zones as z', 'z.id', '=', 'tb.zone_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('o.deleted_at')
            ->whereNull('tb.deleted_at')
            ->whereNull('z.deleted_at')
            ->selectRaw('z.name as name, SUM(s.total) as revenue, COUNT(DISTINCT s.id) as tickets')
            ->groupBy('z.id', 'z.name')
            ->orderByRaw('SUM(s.total) DESC')
            ->get()
            ->map(fn ($r) => [
                'name'    => $r->name,
                'revenue' => (int) $r->revenue,
                'tickets' => (int) $r->tickets,
            ])
            ->toArray();

        $rows = DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->join('products as p', 'p.id', '=', 'sl.product_id')
            ->join('families as f', 'f.id', '=', 'p.family_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereNull('f.deleted_at')
            ->whereNotNull('sl.product_id')
            ->selectRaw('
                p.id as product_id,
                p.name as name,
                f.id as family_id,
                f.name as family,
                p.price as price,
                SUM(sl.quantity) as units,
                SUM(sl.quantity * sl.price) as revenue
            ')
            ->groupBy('p.id', 'p.name', 'f.id', 'f.name', 'p.price')
            ->orderByRaw('SUM(sl.quantity * sl.price) DESC')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'period_revenue' => $periodRevenue,
                'items'          => [],
                'stock_critical' => $stockCritical,
                'no_sales_7d'    => $noSales7d,
                'alert_count'    => $alertCount,
                'by_zone'        => $byZone,
            ];
        }

        $productIds = $rows->pluck('product_id')->all();

        $sparkSince = now()->subDays(13)->toDateString();
        $sparkRows  = DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->whereIn('sl.product_id', $productIds)
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereDate('s.value_date', '>=', $sparkSince)
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->selectRaw('sl.product_id, DATE(s.value_date) as d, SUM(sl.quantity) as qty')
            ->groupBy('sl.product_id', DB::raw('DATE(s.value_date)'))
            ->get();

        $sparkMap = [];
        foreach ($sparkRows as $r) {
            $sparkMap[(int) $r->product_id][$r->d] = (int) $r->qty;
        }

        $dates = [];
        for ($i = 13; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->toDateString();
        }

        $palette = ['#ff4d4d','#1a9e5a','#0077cc','#d18a1c','#7857d6','#3d3d3d','#ff8800','#9b59b6'];
        $familyColors = [];
        $colorIdx = 0;
        $items = [];

        foreach ($rows as $row) {
            $fid = (int) $row->family_id;
            if (!isset($familyColors[$fid])) {
                $familyColors[$fid] = $palette[$colorIdx % count($palette)];
                $colorIdx++;
            }

            $pid   = (int) $row->product_id;
            $units = (int) $row->units;
            $rev   = (int) $row->revenue;
            $pct   = $periodRevenue > 0 ? round(($rev / $periodRevenue) * 100, 2) : 0.0;

            $spark = [];
            foreach ($dates as $d) {
                $spark[] = $sparkMap[$pid][$d] ?? 0;
            }

            $avgDaily = array_sum($spark) / 14;

            $items[] = [
                'name'         => $row->name,
                'family'       => $row->family,
                'family_color' => $familyColors[$fid],
                'units'        => $units,
                'revenue'      => $rev,
                'cost'         => 0,
                'price'        => (int) $row->price,
                'pct'          => $pct,
                'avg_daily'    => round($avgDaily, 2),
                'trend_spark'  => $spark,
            ];
        }

        return [
            'period_revenue' => $periodRevenue,
            'items'          => $items,
            'stock_critical' => $stockCritical,
            'no_sales_7d'    => $noSales7d,
            'alert_count'    => $alertCount,
            'by_zone'        => $byZone,
        ];
    }

    public function getEmployeesReport(int $restaurantId, DateRange $range): array
    {
        $from = $range->from->format('Y-m-d H:i:s');
        $to   = $range->to->format('Y-m-d H:i:s');

        $rows = DB::table('sales as s')
            ->join('sales_lines as sl', function ($join) {
                $join->on('sl.sale_id', '=', 's.id')->whereNull('sl.deleted_at');
            })
            ->join('users as u', 'u.id', '=', 's.opened_by_user_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->selectRaw('
                u.id        as user_id,
                u.uuid      as user_uuid,
                u.name      as name,
                u.role      as role,
                COUNT(DISTINCT s.id) as tickets,
                SUM(s.total)         as revenue,
                SUM(sl.quantity)     as items_sold
            ')
            ->groupBy('u.id', 'u.uuid', 'u.name', 'u.role')
            ->orderByRaw('SUM(s.total) DESC')
            ->get();

        if ($rows->isEmpty()) {
            return ['items' => []];
        }

        $userIds = $rows->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        $tipsRows = DB::table('sale_payments as sp')
            ->join('sales as s', 's.id', '=', 'sp.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->where('sp.method', 'tip')
            ->whereNull('s.deleted_at')
            ->whereNull('sp.deleted_at')
            ->selectRaw('s.opened_by_user_id as user_id, SUM(sp.amount_cents) as tips')
            ->groupBy('s.opened_by_user_id')
            ->get();

        $tipsMap = [];
        foreach ($tipsRows as $r) {
            $tipsMap[(int) $r->user_id] = (int) $r->tips;
        }

        $cancelRows = DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'cancelled')
            ->whereBetween('value_date', [$from, $to])
            ->whereNull('deleted_at')
            ->whereIn('opened_by_user_id', $userIds)
            ->selectRaw('opened_by_user_id as user_id, COUNT(*) as cnt')
            ->groupBy('opened_by_user_id')
            ->get();

        $cancelMap = [];
        foreach ($cancelRows as $r) {
            $cancelMap[(int) $r->user_id] = (int) $r->cnt;
        }

        $sparkSince = now()->subDays(13)->toDateString();
        $sparkRows  = DB::table('sales as s')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereDate('s.value_date', '>=', $sparkSince)
            ->whereNull('s.deleted_at')
            ->whereIn('s.opened_by_user_id', $userIds)
            ->selectRaw('s.opened_by_user_id as user_id, DATE(s.value_date) as d, SUM(s.total) as revenue')
            ->groupByRaw('s.opened_by_user_id, DATE(s.value_date)')
            ->get();

        $sparkMap = [];
        foreach ($sparkRows as $r) {
            $sparkMap[(int) $r->user_id][$r->d] = (int) $r->revenue;
        }

        $dates = [];
        for ($i = 13; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->toDateString();
        }

        $palette = ['#ff4d4d', '#1a9e5a', '#0077cc', '#d18a1c', '#7857d6', '#3d3d3d', '#ff8800', '#9b59b6'];
        $items   = [];

        foreach ($rows as $idx => $row) {
            $uid     = (int) $row->user_id;
            $revenue = (int) $row->revenue;
            $tickets = (int) $row->tickets;

            $spark = [];
            foreach ($dates as $d) {
                $spark[] = $sparkMap[$uid][$d] ?? 0;
            }

            $nameParts = explode(' ', trim($row->name));
            $initials  = count($nameParts) >= 2
                ? strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1))
                : strtoupper(mb_substr($row->name, 0, 2));

            $items[] = [
                'user_uuid'     => $row->user_uuid,
                'name'          => $row->name,
                'role'          => $row->role,
                'initials'      => $initials,
                'color'         => $palette[$idx % count($palette)],
                'tickets'       => $tickets,
                'revenue'       => $revenue,
                'avg_ticket'    => $tickets > 0 ? (int) round($revenue / $tickets) : 0,
                'items_sold'    => (int) $row->items_sold,
                'tips'          => $tipsMap[$uid] ?? 0,
                'discounts'     => 0,
                'cancellations' => $cancelMap[$uid] ?? 0,
                'spark_revenue' => $spark,
            ];
        }

        return ['items' => $items];
    }

    public function getTaxReport(int $restaurantId, DateRange $range, DateRange $qRange, string $quarter, int $year): array
    {
        $from  = $range->from->format('Y-m-d H:i:s');
        $to    = $range->to->format('Y-m-d H:i:s');
        $qFrom = $qRange->from->format('Y-m-d H:i:s');
        $qTo   = $qRange->to->format('Y-m-d H:i:s');

        $restaurant = DB::table('restaurants')
            ->where('id', $restaurantId)
            ->select('name', 'legal_name', 'tax_id')
            ->first();

        $breakdown = $this->buildTaxBreakdown($restaurantId, $from, $to);
        $tipsCard  = $this->fetchTipsCard($restaurantId, $from, $to);
        $quarterly = $this->buildQuarterly($restaurantId, $qFrom, $qTo, $quarter, $year);

        return [
            'period_label' => $range->label,
            'breakdown'    => $breakdown,
            'tips_card'    => $tipsCard,
            'quarterly'    => $quarterly,
            'restaurant'   => [
                'name'       => $restaurant?->name ?? '',
                'legal_name' => $restaurant?->legal_name ?? $restaurant?->name ?? '',
                'tax_id'     => $restaurant?->tax_id ?? '—',
            ],
        ];
    }

    private function buildTaxBreakdown(int $restaurantId, string $from, string $to): array
    {
        $rows = DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->selectRaw('
                sl.tax_percentage                                      AS rate,
                COUNT(DISTINCT sl.product_id)                          AS products,
                SUM(sl.price * sl.quantity)                            AS base,
                SUM(sl.price * sl.quantity * sl.tax_percentage / 100)  AS tax
            ')
            ->groupBy('sl.tax_percentage')
            ->orderBy('sl.tax_percentage')
            ->get();

        $labels = [
            4  => 'Superreducido',
            10 => 'Reducido · hostelería',
            21 => 'General',
        ];

        $result = [];
        foreach ($rows as $row) {
            $rate     = (int) $row->rate;
            $base     = (int) $row->base;
            $tax      = (int) round((float) $row->tax);
            $total    = $base + $tax;
            $products = (int) $row->products;

            $result[] = [
                'rate'  => $rate,
                'label' => $labels[$rate] ?? "IVA {$rate}%",
                'note'  => "{$products} " . ($products === 1 ? 'artículo' : 'artículos') . ' distintos',
                'base'  => $base,
                'tax'   => $tax,
                'total' => $total,
            ];
        }

        return $result;
    }

    private function fetchTipsCard(int $restaurantId, string $from, string $to): int
    {
        $result = DB::table('sale_payments as sp')
            ->join('sales as s', 's.id', '=', 'sp.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->where('sp.method', 'tip')
            ->whereBetween('s.value_date', [$from, $to])
            ->whereNull('s.deleted_at')
            ->whereNull('sp.deleted_at')
            ->value(DB::raw('COALESCE(SUM(sp.amount_cents), 0)'));

        return (int) $result;
    }

    private function buildQuarterly(int $restaurantId, string $qFrom, string $qTo, string $quarter, int $year): array
    {
        $rows = DB::table('sales_lines as sl')
            ->join('sales as s', 's.id', '=', 'sl.sale_id')
            ->where('s.restaurant_id', $restaurantId)
            ->where('s.status', 'closed')
            ->whereBetween('s.value_date', [$qFrom, $qTo])
            ->whereNull('s.deleted_at')
            ->whereNull('sl.deleted_at')
            ->selectRaw('
                sl.tax_percentage                                      AS rate,
                SUM(sl.price * sl.quantity)                            AS base,
                SUM(sl.price * sl.quantity * sl.tax_percentage / 100)  AS tax
            ')
            ->groupBy('sl.tax_percentage')
            ->orderBy('sl.tax_percentage')
            ->get();

        $quarterLabels = [
            'T1' => "T1 · ene-mar {$year}",
            'T2' => "T2 · abr-jun {$year}",
            'T3' => "T3 · jul-sep {$year}",
            'T4' => "T4 · oct-dic {$year}",
        ];

        $rates = [];
        foreach ($rows as $row) {
            $base    = (int) $row->base;
            $tax     = (int) round((float) $row->tax);
            $rates[] = [
                'rate' => (int) $row->rate,
                'base' => $base,
                'tax'  => $tax,
            ];
        }

        return [
            'quarter'     => $quarter,
            'period'      => $quarterLabels[$quarter] ?? "{$quarter} {$year}",
            'elapsed_pct' => DateRange::quarterElapsedPct($year, $quarter),
            'rates'       => $rates,
        ];
    }
}
