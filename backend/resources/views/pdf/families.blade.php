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

    $palette = ['#0077cc','#1a9e5a','#d18a1c','#7857d6','#ff4d4d','#3d3d3d','#ff8800','#9b59b6'];
    $heroDelta = $delta($total, $prevTotal);
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
  .h-brand .doc { display: inline-block; background: #0077cc; color: #fff; padding: 2px 9px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin-top: 4px; }
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
  td { padding: 6px 7px; border-bottom: 1px solid #ececec; font-size: 10px; vertical-align: middle; }
  tr { page-break-inside: avoid; }
  .mono { font-family: 'Courier New', monospace; }
  .muted { color: #888; }
  .total-row td { font-weight: bold; background: #f7f7f7; border-bottom: 2px solid #1a1a1a; }
  .empty { color: #999; font-style: italic; padding: 8px 7px; }
  .fam-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
  .bar-track { background: #eee; height: 8px; width: 100%; }
  .bar-fill { height: 8px; }

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
      <div class="doc">VENTAS POR FAMILIA</div>
    </div>
  </div>
  <div class="period-bar">
    {{ $periodLabel }}
    <span>Generado el {{ $generatedAt }}</span>
  </div>
</div>

<div class="hero">
  <div class="hero-label">Ingresos del periodo</div>
  <div class="hero-value">{{ $eur($total) }} €</div>
  <div class="hero-delta" style="color: {{ $heroDelta[1] === '#999' ? '#bbb' : $heroDelta[1] }}">{{ $heroDelta[0] }} <span style="color:#888; font-weight:normal">vs periodo anterior</span></div>
</div>

<div class="section-title">DISTRIBUCIÓN POR FAMILIA</div>
<table>
  <thead>
    <tr>
      <th>Familia</th>
      <th style="width:120px">% sobre total</th>
      <th class="ar">Uds</th>
      <th class="ar">Ingresos</th>
      <th class="ar">vs anterior</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($families as $i => $f)
      @php
        $pct   = $total > 0 ? round((int) $f['revenue'] / $total * 100, 1) : 0;
        $color = $palette[$i % count($palette)];
        $d     = $delta($f['revenue'] ?? 0, $f['prev_revenue'] ?? 0);
      @endphp
      <tr>
        <td><span class="fam-dot" style="background: {{ $color }}"></span>{{ $f['label'] }}</td>
        <td>
          <table style="margin:0; border:0;"><tr style="border:0;">
            <td style="border:0; padding:0; width:30px;" class="muted mono">{{ number_format($pct, 1, ',', '.') }}%</td>
            <td style="border:0; padding:0 0 0 6px;">
              <div class="bar-track"><div class="bar-fill" style="width: {{ max(2, $pct) }}%; background: {{ $color }};"></div></div>
            </td>
          </tr></table>
        </td>
        <td class="ar mono">{{ $int($f['units'] ?? 0) }}</td>
        <td class="ar mono">{{ $eur($f['revenue'] ?? 0) }} €</td>
        <td class="ar" style="color: {{ $d[1] }}; font-weight: bold;">{{ $d[0] }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="empty">Sin ventas registradas en el periodo.</td></tr>
    @endforelse
    @if (count($families) > 0)
      <tr class="total-row">
        <td>Total</td>
        <td>100,0%</td>
        <td class="ar mono">{{ $int(array_sum(array_column($families, 'units'))) }}</td>
        <td class="ar mono">{{ $eur($total) }} €</td>
        <td class="ar">{{ $heroDelta[0] }}</td>
      </tr>
    @endif
  </tbody>
</table>

<div class="disclaimer">
  Documento generado automáticamente desde Yurest a partir de las operaciones cerradas en el periodo seleccionado.
  Importes con IVA incluido. La comparativa se calcula sobre el periodo inmediatamente anterior de igual duración. Documento informativo sin validez contable.
</div>

</body>
</html>
