<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $eur = fn ($cents) => number_format(((int) $cents) / 100, 2, ',', '.');

    $kpiRevenue = $kpis['revenue']    ?? ['v' => 0, 'delta_pct' => 0];
    $kpiTickets = $kpis['tickets']    ?? ['v' => 0, 'delta_pct' => 0];
    $kpiAvg     = $kpis['avg_ticket'] ?? ['v' => 0, 'delta_pct' => 0];
    $kpiItems   = $kpis['items_sold'] ?? ['v' => 0, 'delta_pct' => 0];
    $kpiDiners  = $kpis['diners']     ?? ['v' => 0, 'delta_pct' => 0];

    $delta = function ($pct) {
        $pct = (float) $pct;
        if ($pct > 0)  return ['▲ +' . number_format($pct, 1, ',', '.') . '%', '#1a9e5a'];
        if ($pct < 0)  return ['▼ '  . number_format($pct, 1, ',', '.') . '%', '#d4351c'];
        return ['—', '#999'];
    };

    $payLabels = [
        'cash' => 'Efectivo', 'card' => 'Tarjeta', 'bizum' => 'Bizum',
        'transfer' => 'Transferencia', 'voucher' => 'Vale', 'other' => 'Otros',
    ];
    $payTotal    = array_sum(array_map('intval', $byPayment));
    $familyTotal = array_sum(array_column($byFamily, 'v'));
@endphp
<style>
  @page { margin: 16mm 15mm; }
  body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }

  .header { border-bottom: 2px solid #1a1a1a; padding-bottom: 12px; margin-bottom: 16px; }
  .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
  .h-business { font-size: 18px; font-weight: bold; margin: 0; }
  .h-meta { font-size: 9px; color: #777; margin-top: 2px; }
  .h-brand { text-align: right; }
  .h-brand .mark { font-size: 13px; font-weight: bold; letter-spacing: .5px; }
  .h-brand .doc { display: inline-block; background: #ff4d4d; color: #fff; padding: 2px 9px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin-top: 4px; }
  .period-bar { margin-top: 10px; background: #f5f5f5; padding: 6px 10px; font-size: 11px; font-weight: bold; }
  .period-bar span { float: right; font-weight: normal; color: #888; font-size: 9px; }

  .hero { background: #1a1a1a; color: #fff; padding: 14px 16px; margin-bottom: 16px; }
  .hero-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #bbb; }
  .hero-value { font-size: 30px; font-weight: bold; line-height: 1.1; margin-top: 2px; }
  .hero-delta { font-size: 11px; font-weight: bold; margin-top: 4px; }

  .section-title { background: #1a1a1a; color: #fff; padding: 4px 8px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin: 16px 0 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  th { background: #f0f0f0; font-size: 7px; text-transform: uppercase; padding: 5px 7px; text-align: left; letter-spacing: .3px; color: #555; }
  th.ar, td.ar { text-align: right; }
  td { padding: 5px 7px; border-bottom: 1px solid #ececec; font-size: 10px; }
  .mono { font-family: 'Courier New', monospace; }
  .muted { color: #888; }
  .total-row td { font-weight: bold; background: #f7f7f7; border-bottom: 2px solid #1a1a1a; }
  .empty { color: #999; font-style: italic; padding: 8px 7px; }

  .disclaimer { font-size: 7px; color: #999; margin-top: 22px; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
  <div class="header-top">
    <div>
      <p class="h-business">{{ $restaurant['name'] ?: $restaurant['legal_name'] }}</p>
      <div class="h-meta">{{ $restaurant['legal_name'] }} · NIF {{ $restaurant['tax_id'] }}</div>
    </div>
    <div class="h-brand">
      <div class="mark">Yurest</div>
      <div class="doc">RESUMEN DIARIO</div>
    </div>
  </div>
  <div class="period-bar">
    {{ $periodLabel }}
    <span>Generado el {{ $generatedAt }}</span>
  </div>
</div>

@php $rev = $delta($kpiRevenue['delta_pct']); @endphp
<div class="hero">
  <div class="hero-label">Ingresos del periodo</div>
  <div class="hero-value">{{ $eur($kpiRevenue['v']) }} €</div>
  <div class="hero-delta" style="color: {{ $rev[1] === '#999' ? '#bbb' : $rev[1] }}">{{ $rev[0] }} <span style="color:#888; font-weight:normal">vs periodo anterior</span></div>
</div>

<div class="section-title">INDICADORES CLAVE</div>
<table>
  <thead>
    <tr>
      <th>Métrica</th>
      <th class="ar">Valor</th>
      <th class="ar">vs periodo anterior</th>
    </tr>
  </thead>
  <tbody>
    @php
      $rows = [
        ['Ingresos',           $eur($kpiRevenue['v']) . ' €', $kpiRevenue['delta_pct']],
        ['Tickets',            number_format((int) $kpiTickets['v'], 0, ',', '.'), $kpiTickets['delta_pct']],
        ['Ticket medio',       $eur($kpiAvg['v']) . ' €', $kpiAvg['delta_pct']],
        ['Artículos vendidos', number_format((int) $kpiItems['v'], 0, ',', '.'), $kpiItems['delta_pct']],
        ['Comensales',         number_format((int) $kpiDiners['v'], 0, ',', '.'), $kpiDiners['delta_pct']],
      ];
    @endphp
    @foreach ($rows as [$label, $value, $pct])
      @php $d = $delta($pct); @endphp
      <tr>
        <td>{{ $label }}</td>
        <td class="ar mono">{{ $value }}</td>
        <td class="ar" style="color: {{ $d[1] }}; font-weight: bold;">{{ $d[0] }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<div class="section-title">DESGLOSE POR MÉTODO DE PAGO</div>
<table>
  <thead>
    <tr>
      <th>Método</th>
      <th class="ar">Importe</th>
      <th class="ar">% sobre total</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($byPayment as $method => $amount)
      <tr>
        <td>{{ $payLabels[$method] ?? ucfirst($method) }}</td>
        <td class="ar mono">{{ $eur($amount) }} €</td>
        <td class="ar muted">{{ $payTotal > 0 ? number_format((int) $amount / $payTotal * 100, 1, ',', '.') : '0,0' }}%</td>
      </tr>
    @empty
      <tr><td colspan="3" class="empty">Sin movimientos registrados en el periodo.</td></tr>
    @endforelse
    @if ($payTotal > 0)
      <tr class="total-row">
        <td>Total</td>
        <td class="ar mono">{{ $eur($payTotal) }} €</td>
        <td class="ar">100,0%</td>
      </tr>
    @endif
  </tbody>
</table>

<div class="section-title">VENTAS POR FAMILIA</div>
<table>
  <thead>
    <tr>
      <th>Familia</th>
      <th class="ar">Ingresos</th>
      <th class="ar">% sobre total</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($byFamily as $f)
      <tr>
        <td>{{ $f['label'] }}</td>
        <td class="ar mono">{{ $eur($f['v']) }} €</td>
        <td class="ar muted">{{ $familyTotal > 0 ? number_format((int) $f['v'] / $familyTotal * 100, 1, ',', '.') : '0,0' }}%</td>
      </tr>
    @empty
      <tr><td colspan="3" class="empty">Sin ventas registradas en el periodo.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="section-title">PRODUCTOS MÁS VENDIDOS</div>
<table>
  <thead>
    <tr>
      <th style="width:24px">#</th>
      <th>Producto</th>
      <th>Familia</th>
      <th class="ar">Unidades</th>
      <th class="ar">Ingresos</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($topProducts as $i => $p)
      <tr>
        <td class="muted">{{ $i + 1 }}</td>
        <td>{{ $p['name'] }}</td>
        <td class="muted">{{ $p['family'] ?? '—' }}</td>
        <td class="ar mono">{{ number_format((int) ($p['units'] ?? 0), 0, ',', '.') }}</td>
        <td class="ar mono">{{ $eur($p['revenue'] ?? 0) }} €</td>
      </tr>
    @empty
      <tr><td colspan="5" class="empty">Sin ventas registradas en el periodo.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="disclaimer">
  Documento generado automáticamente desde Yurest a partir de las operaciones cerradas en el periodo seleccionado.
  Las cifras corresponden a ventas con estado cerrado e incluyen IVA. Documento informativo sin validez contable.
</div>

</body>
</html>
