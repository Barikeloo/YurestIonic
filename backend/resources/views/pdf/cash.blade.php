<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@php
    $eur = fn ($cents) => number_format(((int) $cents) / 100, 2, ',', '.');
    $dt  = fn ($s) => $s ? date('d/m/Y H:i', strtotime($s)) : '—';
    $tm  = fn ($s) => $s ? date('H:i', strtotime($s)) : '—';

    $signed = function ($cents) use ($eur) {
        $cents = (int) $cents;
        if ($cents > 0) return ['+' . $eur($cents) . ' €', '#1a9e5a'];
        if ($cents < 0) return [$eur($cents) . ' €', '#d4351c'];
        return [$eur(0) . ' €', '#888'];
    };

    $reasonLabels = [
        'change_refill'   => 'Recambio de cambio',
        'supplier_payment'=> 'Pago a proveedor',
        'tip_declared'    => 'Propina declarada',
        'sangria'         => 'Sangría',
        'adjustment'      => 'Ajuste',
        'cancellation'    => 'Cancelación',
        'other'           => 'Otros',
    ];

    $heroNet = $signed($net);
@endphp
<style>
  @page { margin: 16mm 14mm; }
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

  .hero { background: #1a1a1a; color: #fff; padding: 14px 16px; margin-bottom: 8px; }
  .hero-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #bbb; }
  .hero-value { font-size: 28px; font-weight: bold; line-height: 1.1; margin-top: 2px; }

  .stats { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  .stats td { width: 33%; border: 1px solid #e8e8e8; padding: 8px 10px; }
  .stat-label { font-size: 7px; text-transform: uppercase; letter-spacing: .5px; color: #999; }
  .stat-value { font-size: 14px; font-weight: bold; margin-top: 2px; }

  .section-title { background: #1a1a1a; color: #fff; padding: 4px 8px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin: 16px 0 6px; }
  table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  table.data th { background: #f0f0f0; font-size: 7px; text-transform: uppercase; padding: 5px 6px; text-align: left; letter-spacing: .3px; color: #555; }
  table.data th.ar, table.data td.ar { text-align: right; }
  table.data td { padding: 5px 6px; border-bottom: 1px solid #ececec; font-size: 9.5px; vertical-align: top; }
  table.data tr { page-break-inside: avoid; }
  .mono { font-family: 'Courier New', monospace; }
  .muted { color: #888; }
  .sub { font-size: 7.5px; color: #999; }
  .total-row td { font-weight: bold; background: #f7f7f7; border-bottom: 2px solid #1a1a1a; }
  .empty { color: #999; font-style: italic; padding: 8px 6px; }
  .badge { display: inline-block; padding: 1px 6px; font-size: 7.5px; font-weight: bold; border-radius: 3px; }
  .badge-in  { background: #e6f4ec; color: #1a9e5a; }
  .badge-out { background: #fdeaea; color: #d4351c; }

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
      <div class="doc">MOVIMIENTOS DE CAJA</div>
    </div>
  </div>
  <div class="period-bar">
    {{ $periodLabel }}
    <span>Generado el {{ $generatedAt }}</span>
  </div>
</div>

<div class="hero">
  <div class="hero-label">Efectivo neto de movimientos manuales</div>
  <div class="hero-value">{{ $heroNet[0] }}</div>
</div>
<table class="stats">
  <tr>
    <td>
      <div class="stat-label">Entradas</div>
      <div class="stat-value" style="color:#1a9e5a">+{{ $eur($totalIn) }} €</div>
    </td>
    <td>
      <div class="stat-label">Salidas</div>
      <div class="stat-value" style="color:#d4351c">-{{ $eur($totalOut) }} €</div>
    </td>
    <td>
      @php $dt2 = $signed($discrepancyTotal); @endphp
      <div class="stat-label">Descuadre acumulado</div>
      <div class="stat-value" style="color: {{ $dt2[1] }}">{{ $dt2[0] }}</div>
    </td>
  </tr>
</table>

<div class="section-title">SESIONES CERRADAS</div>
<table class="data">
  <thead>
    <tr>
      <th>Cierre</th>
      <th>Cerrada por</th>
      <th class="ar">Fondo inicial</th>
      <th class="ar">Esperado</th>
      <th class="ar">Contado</th>
      <th class="ar">Descuadre</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($sessions as $s)
      @php $d = $signed($s['discrepancy'] ?? 0); @endphp
      <tr>
        <td>
          {{ $dt($s['closed_at']) }}
          <div class="sub">Apertura {{ $tm($s['opened_at']) }}@if (!empty($s['z_report_number'])) · Z #{{ $s['z_report_number'] }}@endif</div>
        </td>
        <td>{{ $s['closed_by'] }}</td>
        <td class="ar mono">{{ $eur($s['initial_amount'] ?? 0) }} €</td>
        <td class="ar mono">{{ $eur($s['expected_amount'] ?? 0) }} €</td>
        <td class="ar mono">{{ $eur($s['final_amount'] ?? 0) }} €</td>
        <td class="ar mono" style="color: {{ $d[1] }}; font-weight: bold;">
          {{ $d[0] }}
          @if (!empty($s['discrepancy_reason']))<div class="sub" style="font-weight:normal">{{ $s['discrepancy_reason'] }}</div>@endif
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">No hay sesiones de caja cerradas en el periodo seleccionado.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="section-title">MOVIMIENTOS MANUALES DE EFECTIVO</div>
<table class="data">
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Tipo</th>
      <th>Motivo</th>
      <th>Usuario</th>
      <th class="ar">Importe</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($movements as $m)
      <tr>
        <td class="mono">{{ $dt($m['created_at']) }}</td>
        <td>
          @if (($m['type'] ?? '') === 'in')
            <span class="badge badge-in">Entrada</span>
          @else
            <span class="badge badge-out">Salida</span>
          @endif
        </td>
        <td>
          {{ $reasonLabels[$m['reason_code'] ?? 'other'] ?? 'Otros' }}
          @if (!empty($m['description']))<div class="sub">{{ $m['description'] }}</div>@endif
        </td>
        <td class="muted">{{ $m['user_name'] }}</td>
        <td class="ar mono" style="color: {{ ($m['type'] ?? '') === 'in' ? '#1a9e5a' : '#d4351c' }}; font-weight: bold;">
          {{ ($m['type'] ?? '') === 'in' ? '+' : '-' }}{{ $eur($m['amount'] ?? 0) }} €
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="empty">No se han registrado movimientos manuales en el periodo seleccionado.</td></tr>
    @endforelse
    @if (count($movements) > 0)
      <tr class="total-row">
        <td colspan="4">Neto</td>
        <td class="ar mono" style="color: {{ $heroNet[1] }}">{{ $heroNet[0] }}</td>
      </tr>
    @endif
  </tbody>
</table>

<div class="disclaimer">
  Documento generado automáticamente desde Yurest. Incluye las sesiones de caja cerradas dentro del periodo seleccionado y sus movimientos manuales de efectivo.
  El descuadre es la diferencia entre el efectivo contado y el esperado al cierre. Documento informativo de control interno, sin validez contable.
</div>

</body>
</html>
