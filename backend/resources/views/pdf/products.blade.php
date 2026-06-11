<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $eur = fn ($cents) => number_format(((int) $cents) / 100, 2, ',', '.');
    $int = fn ($v) => number_format((int) $v, 0, ',', '.');

    $delta = function ($v, $prev) {
        $v = (int) $v; $prev = (int) $prev;
        if ($prev <= 0) return ['—', '#999'];
        $pct = round(($v - $prev) / $prev * 100, 1);
        if ($pct > 0)  return ['▲ +' . number_format($pct, 1, ',', '.') . '%', '#1a9e5a'];
        if ($pct < 0)  return ['▼ '  . number_format($pct, 1, ',', '.') . '%', '#d4351c'];
        return ['=', '#999'];
    };

    $totalUnits = array_sum(array_column($items, 'units'));
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
  .h-brand .doc { display: inline-block; background: #d18a1c; color: #fff; padding: 2px 9px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin-top: 4px; }
  .period-bar { margin-top: 10px; background: #f5f5f5; padding: 6px 10px; font-size: 11px; font-weight: bold; }
  .period-bar span { float: right; font-weight: normal; color: #888; font-size: 9px; }

  .hero { background: #1a1a1a; color: #fff; padding: 12px 16px; margin-bottom: 16px; }
  .hero-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #bbb; }
  .hero-value { font-size: 28px; font-weight: bold; line-height: 1.1; margin-top: 2px; }
  .hero-meta { font-size: 9px; color: #bbb; margin-top: 4px; }

  .section-title { background: #1a1a1a; color: #fff; padding: 4px 8px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin: 16px 0 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  th { background: #f0f0f0; font-size: 7px; text-transform: uppercase; padding: 5px 7px; text-align: left; letter-spacing: .3px; color: #555; }
  th.ar, td.ar { text-align: right; }
  td { padding: 4px 7px; border-bottom: 1px solid #ececec; font-size: 9.5px; }
  tr { page-break-inside: avoid; }
  .mono { font-family: 'Courier New', monospace; }
  .muted { color: #888; }
  .total-row td { font-weight: bold; background: #f7f7f7; border-bottom: 2px solid #1a1a1a; }
  .empty { color: #999; font-style: italic; padding: 8px 7px; }
  .fam-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; margin-right: 4px; }

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
      <div class="doc">VENTAS POR PRODUCTO</div>
    </div>
  </div>
  <div class="period-bar">
    {{ $periodLabel }}
    <span>Generado el {{ $generatedAt }}</span>
  </div>
</div>

<div class="hero">
  <div class="hero-label">Facturación del periodo</div>
  <div class="hero-value">{{ $eur($periodRevenue) }} €</div>
  <div class="hero-meta">{{ $int(count($items)) }} productos vendidos · {{ $int($totalUnits) }} unidades</div>
</div>

<div class="section-title">RANKING DE VENTAS</div>
<table>
  <thead>
    <tr>
      <th style="width:22px">#</th>
      <th>Producto</th>
      <th>Familia</th>
      <th class="ar">Uds</th>
      <th class="ar">Precio</th>
      <th class="ar">Ingresos</th>
      <th class="ar">% total</th>
      <th class="ar">vs anterior</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($items as $i => $p)
      @php $d = $delta($p['revenue'] ?? 0, $p['prev_revenue'] ?? 0); @endphp
      <tr>
        <td class="muted">{{ $i + 1 }}</td>
        <td>{{ $p['name'] }}</td>
        <td class="muted">
          @if (!empty($p['family_color']))<span class="fam-dot" style="background: {{ $p['family_color'] }}"></span>@endif{{ $p['family'] ?? '—' }}
        </td>
        <td class="ar mono">{{ $int($p['units'] ?? 0) }}</td>
        <td class="ar mono">{{ $eur($p['price'] ?? 0) }}</td>
        <td class="ar mono">{{ $eur($p['revenue'] ?? 0) }} €</td>
        <td class="ar muted">{{ number_format((float) ($p['pct'] ?? 0), 1, ',', '.') }}%</td>
        <td class="ar" style="color: {{ $d[1] }}; font-weight: bold;">{{ $d[0] }}</td>
      </tr>
    @empty
      <tr><td colspan="8" class="empty">Sin ventas registradas en el periodo.</td></tr>
    @endforelse
    @if (count($items) > 0)
      <tr class="total-row">
        <td colspan="3">Total</td>
        <td class="ar mono">{{ $int($totalUnits) }}</td>
        <td></td>
        <td class="ar mono">{{ $eur($periodRevenue) }} €</td>
        <td class="ar">100,0%</td>
        <td></td>
      </tr>
    @endif
  </tbody>
</table>

@if (!empty($byZone))
<div class="section-title">VENTAS POR ZONA</div>
<table>
  <thead>
    <tr>
      <th>Zona</th>
      <th class="ar">Tickets</th>
      <th class="ar">Ingresos</th>
      <th class="ar">% sobre total</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($byZone as $z)
      <tr>
        <td>{{ $z['name'] }}</td>
        <td class="ar mono">{{ $int($z['tickets'] ?? 0) }}</td>
        <td class="ar mono">{{ $eur($z['revenue'] ?? 0) }} €</td>
        <td class="ar muted">{{ $periodRevenue > 0 ? number_format((int) ($z['revenue'] ?? 0) / $periodRevenue * 100, 1, ',', '.') : '0,0' }}%</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

@if (!empty($stockCritical))
<div class="section-title">STOCK CRÍTICO</div>
<table>
  <thead>
    <tr>
      <th>Producto</th>
      <th>Familia</th>
      <th class="ar">Stock restante</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($stockCritical as $s)
      <tr>
        <td>{{ $s['name'] }}</td>
        <td class="muted">{{ $s['family'] ?? '—' }}</td>
        <td class="ar mono" style="color: {{ ((int) ($s['stock'] ?? 0)) <= 5 ? '#d4351c' : '#d18a1c' }}; font-weight: bold;">{{ $int($s['stock'] ?? 0) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

<div class="disclaimer">
  Documento generado automáticamente desde Yurest a partir de las operaciones cerradas en el periodo seleccionado.
  Importes con IVA incluido. El ranking incluye únicamente productos con ventas en el periodo. Documento informativo sin validez contable.
</div>

</body>
</html>
