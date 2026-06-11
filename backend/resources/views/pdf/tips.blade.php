<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $eur = fn ($cents) => number_format(((int) $cents) / 100, 2, ',', '.');
    $int = fn ($v) => number_format((int) $v, 0, ',', '.');
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
  .h-brand .doc { display: inline-block; background: #1a9e5a; color: #fff; padding: 2px 9px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin-top: 4px; }
  .period-bar { margin-top: 10px; background: #f5f5f5; padding: 6px 10px; font-size: 11px; font-weight: bold; }
  .period-bar span { float: right; font-weight: normal; color: #888; font-size: 9px; }

  .hero { background: #1a1a1a; color: #fff; padding: 14px 16px; margin-bottom: 16px; }
  .hero-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #bbb; }
  .hero-value { font-size: 30px; font-weight: bold; line-height: 1.1; margin-top: 2px; }
  .hero-meta { font-size: 9px; color: #bbb; margin-top: 4px; }

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
  .emp-dot { display: inline-block; width: 18px; height: 18px; border-radius: 50%; color: #fff; font-size: 7px; font-weight: bold; text-align: center; line-height: 18px; margin-right: 6px; }
  .role { font-size: 8px; color: #999; text-transform: capitalize; }

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
      <div class="doc">PROPINAS DECLARADAS</div>
    </div>
  </div>
  <div class="period-bar">
    {{ $periodLabel }}
    <span>Generado el {{ $generatedAt }}</span>
  </div>
</div>

<div class="hero">
  <div class="hero-label">Propinas declaradas en el periodo</div>
  <div class="hero-value">{{ $eur($totalTips) }} €</div>
  <div class="hero-meta">{{ $int(count($employees)) }} {{ count($employees) === 1 ? 'empleado' : 'empleados' }} con propina</div>
</div>

<div class="section-title">PROPINAS POR EMPLEADO</div>
<table>
  <thead>
    <tr>
      <th>Empleado</th>
      <th class="ar">Tickets</th>
      <th class="ar">Ventas</th>
      <th class="ar">Propinas</th>
      <th class="ar">% s/ ventas</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($employees as $e)
      @php
        $tips    = (int) ($e['tips'] ?? 0);
        $revenue = (int) ($e['revenue'] ?? 0);
        $ratio   = $revenue > 0 ? round($tips / $revenue * 100, 1) : 0;
      @endphp
      <tr>
        <td>
          <span class="emp-dot" style="background: {{ $e['color'] ?? '#3d3d3d' }}">{{ $e['initials'] ?? '' }}</span>
          {{ $e['name'] }}
          @if (!empty($e['role']))<span class="role"> · {{ $e['role'] }}</span>@endif
        </td>
        <td class="ar mono">{{ $int($e['tickets'] ?? 0) }}</td>
        <td class="ar mono">{{ $eur($revenue) }} €</td>
        <td class="ar mono" style="font-weight: bold; color: #1a9e5a;">{{ $eur($tips) }} €</td>
        <td class="ar muted">{{ number_format($ratio, 1, ',', '.') }}%</td>
      </tr>
    @empty
      <tr><td colspan="5" class="empty">No se han declarado propinas en el periodo seleccionado.</td></tr>
    @endforelse
    @if (count($employees) > 0)
      <tr class="total-row">
        <td>Total</td>
        <td></td>
        <td></td>
        <td class="ar mono">{{ $eur($totalTips) }} €</td>
        <td></td>
      </tr>
    @endif
  </tbody>
</table>

<div class="disclaimer">
  Documento generado automáticamente desde Yurest a partir de las propinas registradas como medio de pago en las operaciones cerradas del periodo.
  Las propinas se atribuyen al empleado que abrió la venta. Documento informativo de uso interno, sin validez contable ni fiscal.
</div>

</body>
</html>
