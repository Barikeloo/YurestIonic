import { Component, inject, signal } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';

interface BarItem {
  x: number; w: number; h: number; hPrev: number;
  v: number; vPrev: number; n: number; nPrev: number;
  label: string; isPeak: boolean;
}

interface DonutSeg {
  color: string; label: string; v: number; frac: number;
  dashArray: string; dashOffset: string;
}

@Component({
  selector: 'app-finanzas-resumen-tab',
  templateUrl: './resumen-tab.component.html',
  styleUrls: ['./resumen-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class ResumenTabComponent {
  protected readonly facade = inject(FinanzasFacade);
  protected readonly s = this.facade.summary;

  protected readonly hoveredBar  = signal<number | null>(null);
  protected readonly hoveredDonut = signal<number | null>(null);

  protected fmt(v: number):    string { return this.facade.fmt(v); }
  protected fmtNum(v: number): string { return this.facade.fmtNum(v); }
  protected fmtInt(v: number): string { return this.facade.fmtInt(v); }
  protected fmtPct(v: number): string {
    return `${v >= 0 ? '+' : ''}${v.toFixed(1).replace('.', ',')}%`;
  }

  protected trendColor(d: number):  string { return d >= 0 ? '#1a9e5a' : '#ff4d4d'; }
  protected trendBg(d: number):     string { return d >= 0 ? '#e8f7ef' : '#ffecec'; }
  protected trendArrow(d: number):  string { return d >= 0 ? '↑' : '↓'; }

  // ── Sparkline ──────────────────────────────────────────────────────────────
  protected sparkPath(data: number[], W = 100, H = 24): string {
    if (!data.length) return '';
    const max = Math.max(...data, 1);
    const min = Math.min(...data, 0);
    const range = max - min || 1;
    return data.map((v, i) => {
      const x = (i / (data.length - 1)) * W;
      const y = H - 2 - ((v - min) / range) * (H - 4);
      return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
    }).join(' ');
  }

  protected sparkArea(data: number[], W = 100, H = 24): string {
    const path = this.sparkPath(data, W, H);
    if (!path) return '';
    return `${path} L ${W} ${H} L 0 ${H} Z`;
  }

  protected sparkDot(data: number[], W = 100, H = 24): { cx: number; cy: number } {
    if (!data.length) return { cx: 0, cy: 0 };
    const max = Math.max(...data, 1);
    const min = Math.min(...data, 0);
    const range = max - min || 1;
    const last = data[data.length - 1];
    return { cx: W, cy: H - 2 - ((last - min) / range) * (H - 4) };
  }

  // ── BarChart ───────────────────────────────────────────────────────────────
  // Uses SVG viewBox 0 0 100 100 (same as design)
  protected readonly CHART_PAD = 2;

  protected get barItems(): BarItem[] {
    const data  = this.facade.byHour;
    const prev  = this.facade.byHourPrev;
    const compare = this.facade.showCompare();
    const allVals = [...data.map(d => d.v), ...(compare ? prev.map(d => d.v) : [])];
    const max = Math.max(...allVals, 1);
    const n = data.length;
    const barW = (100 - this.CHART_PAD * 2) / n;
    const peakIdx = data.reduce((m, d, i) => d.v > data[m].v ? i : m, 0);

    return data.map((d, i) => ({
      x:     this.CHART_PAD + i * barW,
      w:     barW,
      h:     (d.v / max) * 95,
      hPrev: (prev[i]?.v ?? 0) / max * 95,
      v:     d.v,
      vPrev: prev[i]?.v ?? 0,
      n:     (d as any).n ?? 0,
      nPrev: 0,
      label: d.l,
      isPeak: i === peakIdx,
    }));
  }

  protected get avgLine(): number {
    const data = this.facade.byHour;
    const avg  = data.reduce((s, d) => s + d.v, 0) / data.length;
    const max  = Math.max(...data.map(d => d.v), 1);
    return (1 - avg / max) * 95;
  }

  protected get peakIdx(): number {
    const data = this.facade.byHour;
    return data.reduce((m, d, i) => d.v > data[m].v ? i : m, 0);
  }

  protected setHoveredBar(i: number | null): void { this.hoveredBar.set(i); }

  protected get tooltipInfo(): { leftPct: string; bar: BarItem; compare: boolean } | null {
    const idx = this.hoveredBar();
    if (idx === null) return null;
    const bar = this.barItems[idx];
    if (!bar) return null;
    const leftPct = `${((bar.x + bar.w / 2) / 100) * 100}%`;
    return { leftPct, bar, compare: this.facade.showCompare() };
  }

  // ── Donut ──────────────────────────────────────────────────────────────────
  protected readonly donutSize      = 140;
  protected readonly donutThickness = 22;

  protected get donutCenter(): number { return this.donutSize / 2; }
  protected get donutRad():    number { return (this.donutSize - this.donutThickness) / 2; }

  protected get donutSegs(): DonutSeg[] {
    return this.facade.donutSegments(this.facade.byFamily, this.donutSize, this.donutThickness);
  }

  protected get donutTotal(): number {
    return this.facade.byFamily.reduce((s, d) => s + d.v, 0) || 1;
  }

  protected getDonutStrokeW(i: number): number {
    return this.hoveredDonut() === i ? this.donutThickness + 2 : this.donutThickness;
  }

  // ── Open tables ────────────────────────────────────────────────────────────
  protected tableStateLabel(s: string): string {
    const m: Record<string, string> = { eating: 'Comiendo', paying: 'Cobrando', ordering: 'Pidiendo', idle: 'Inactiva' };
    return m[s] || s;
  }

  protected tableStateColor(s: string): string {
    const m: Record<string, string> = { eating: '#1a9e5a', paying: '#d18a1c', ordering: '#0077cc', idle: '#7a7a7a' };
    return m[s] || '#a0a0a0';
  }

  protected get openTablesTotal(): number {
    return this.facade.openTables.reduce((s, t) => s + t.current, 0);
  }

  // ── Methods ────────────────────────────────────────────────────────────────
  protected methodEntries(): { key: string; label: string; color: string; v: number; n: number }[] {
    const m = this.facade.byMethod;
    return [
      { key: 'card',       label: 'Tarjeta',    color: '#3d3d3d', ...m.card       },
      { key: 'cash',       label: 'Efectivo',   color: '#1a9e5a', ...m.cash       },
      { key: 'bizum',      label: 'Bizum',      color: '#0077cc', ...m.bizum      },
      { key: 'voucher',    label: 'Bono',       color: '#7857d6', ...m.voucher    },
      { key: 'invitation', label: 'Invitación', color: '#ff4d4d', ...m.invitation },
    ];
  }

  // ── Forecast ───────────────────────────────────────────────────────────────
  protected get forecastProgress(): number {
    const f = this.facade.forecast;
    return (f.closed / f.projection) * 100;
  }

  // ── Pending payments ───────────────────────────────────────────────────────
  protected get pendingTotal(): number {
    return this.facade.pendingPayments.reduce((s, p) => s + p.total, 0);
  }

  // ── Insights ───────────────────────────────────────────────────────────────
  protected readonly insights = [
    { icon: '★', text: 'Pico de comida a las 14h con 394 € · refuerza turno', color: '#ff4d4d' },
    { icon: '↑', text: 'Bebidas tira el carro: 36% del total (vs 32% ayer)',  color: '#1a9e5a' },
    { icon: '⚠', text: '4 productos sin ventas en 7 días · revisar carta',    color: '#d18a1c' },
  ];
}
