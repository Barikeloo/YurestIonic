<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 18mm 15mm; }
  body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
  .header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #1a1a1a; padding-bottom: 12px; }
  .header h1 { font-size: 20px; margin: 0 0 4px; }
  .header p { margin: 2px 0; font-size: 11px; color: #555; }
  .badge { display: inline-block; background: #1a1a1a; color: #fff; padding: 2px 10px; font-size: 9px; font-weight: bold; letter-spacing: 1px; }
  .fields { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
  .field { flex: 1 1 45%; }
  .field-label { font-size: 7px; text-transform: uppercase; color: #888; letter-spacing: .5px; }
  .field-value { font-size: 12px; font-weight: bold; }
  .section-title { background: #1a1a1a; color: #fff; padding: 4px 8px; font-size: 8px; font-weight: bold; letter-spacing: 1px; margin: 14px 0 6px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
  th { background: #f0f0f0; font-size: 7px; text-transform: uppercase; padding: 5px 6px; text-align: left; letter-spacing: .3px; }
  th.ar { text-align: right; }
  td { padding: 5px 6px; border-bottom: 1px solid #e0e0e0; font-size: 10px; }
  td.ar { text-align: right; }
  .mono { font-family: 'Courier New', monospace; }
  .total-row td { font-weight: bold; background: #f7f7f7; border-bottom: 2px solid #1a1a1a; }
  .result { display: flex; justify-content: space-between; align-items: center; background: #1a1a1a; color: #fff; padding: 10px 12px; margin-bottom: 10px; }
  .result-label { font-size: 10px; font-weight: bold; }
  .result-value { font-size: 20px; font-weight: bold; }
  .disclaimer { font-size: 7px; color: #999; margin-top: 20px; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; }
  .casilla { display: inline-block; background: #e8e8e8; padding: 0 5px; font-size: 8px; font-weight: bold; border-radius: 2px; margin-right: 2px; }
</style>
</head>
<body>

<div class="header">
  <h1>Modelo 303 — IVA Autoliquidación</h1>
  <p>Régimen general · {{ $period }}</p>
  <span class="badge">BORRADOR</span>
</div>

<div class="fields">
  <div class="field">
    <div class="field-label">Razón social</div>
    <div class="field-value">{{ $legalName }}</div>
  </div>
  <div class="field">
    <div class="field-label">NIF / CIF</div>
    <div class="field-value">{{ $taxId }}</div>
  </div>
  <div class="field">
    <div class="field-label">Nombre comercial</div>
    <div class="field-value">{{ $businessName }}</div>
  </div>
  <div class="field">
    <div class="field-label">Periodo</div>
    <div class="field-value">{{ $period }}</div>
  </div>
</div>

<div class="section-title">IVA DEVENGADO</div>
<table>
  <thead>
    <tr>
      <th>Casilla</th>
      <th>Concepto</th>
      <th class="ar">Base imponible</th>
      <th class="ar">Tipo</th>
      <th class="ar">Cuota</th>
    </tr>
  </thead>
  <tbody>
    @php
      $casillas = [4 => ['01','02','03'], 10 => ['04','05','06'], 21 => ['07','08','09']];
      $conceptos = [4 => 'Régimen general 4%', 10 => 'Régimen general 10%', 21 => 'Régimen general 21%'];
    @endphp
    @foreach ($rates as $r)
      <tr>
        <td>
          @foreach ($casillas[$r['rate']] ?? ['—'] as $c)
            <span class="casilla">{{ $c }}</span>
          @endforeach
        </td>
        <td>{{ $conceptos[$r['rate']] ?? 'IVA ' . $r['rate'] . '%' }}</td>
        <td class="ar mono">{{ number_format($r['base'] / 100, 2, ',', '.') }}</td>
        <td class="ar mono">{{ $r['rate'] }}%</td>
        <td class="ar mono">{{ number_format($r['tax'] / 100, 2, ',', '.') }}</td>
      </tr>
    @endforeach
    <tr class="total-row">
      <td colspan="2">Total IVA devengado <span class="casilla">27</span></td>
      <td class="ar mono">{{ number_format($totalBase / 100, 2, ',', '.') }}</td>
      <td></td>
      <td class="ar mono">{{ number_format($totalTax / 100, 2, ',', '.') }}</td>
    </tr>
  </tbody>
</table>

<div class="section-title">RESULTADO</div>
<div class="result">
  <span class="result-label">Resultado a ingresar <span class="casilla" style="background:#555;color:#fff;">71</span></span>
  <span class="result-value">{{ number_format($totalTax / 100, 2, ',', '.') }} €</span>
</div>

<div class="disclaimer">
  Documento generado automáticamente desde Yurest a partir de las operaciones registradas en el trimestre.
  Debe ser revisado por su asesor fiscal antes de la presentación.
  Esta versión es un borrador y no tiene validez como autoliquidación oficial.
</div>

</body>
</html>
