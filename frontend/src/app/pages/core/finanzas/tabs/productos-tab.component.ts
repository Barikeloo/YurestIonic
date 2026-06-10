import { Component, inject, signal, computed, effect } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { SlicePipe } from '@angular/common';
import { FinanzasFacade } from '../facades/finanzas.facade';
import { IconComponent, type IconName } from '../../../../shared/components/icon/icon.component';
import type { ProductReportItem } from '../models/finanzas.models';

type SortKey    = 'revenue' | 'units' | 'margin' | 'marginPct' | 'stock' | 'rotation';
type QuadrantKey = 'star' | 'enigma' | 'horse' | 'dog';

type Enriched = ProductReportItem & {
  margin:     number;
  mPct:       number;
  days:       number;
  alert:      'critical' | 'low' | null;
  abc:        'A' | 'B' | 'C';
  revDelta:   number | null;
  unitsDelta: number | null;
};

type MatrixPoint = Enriched & { cx: number; cy: number; quadrant: QuadrantKey };

const QMETA: Record<QuadrantKey, { color: string; label: string; icon: IconName; desc: string }> = {
  star:   { color: '#1a9e5a', label: 'Estrella', icon: 'star',          desc: 'Alta venta · altos ingresos — proteger y potenciar' },
  enigma: { color: '#0077cc', label: 'Enigma',   icon: 'gem',           desc: 'Alto ingreso · poca venta — mejorar visibilidad en carta' },
  horse:  { color: '#d18a1c', label: 'Caballo',  icon: 'bar-chart',     desc: 'Mucha venta · bajo ingreso — revisar precio de venta' },
  dog:    { color: '#9b9b9b', label: 'Perro',    icon: 'trending-down', desc: 'Poca venta · bajo ingreso — evaluar retirada de carta' },
};

@Component({
  selector: 'app-finanzas-productos-tab',
  templateUrl: './productos-tab.component.html',
  styleUrls: ['./productos-tab.component.scss'],
  standalone: true,
  imports: [FormsModule, SlicePipe, IconComponent],
})
export class ProductosTabComponent {
  protected readonly facade    = inject(FinanzasFacade);
  protected readonly QMETA     = QMETA;
  protected readonly quadKeys: QuadrantKey[] = ['star', 'enigma', 'horse', 'dog'];
  protected readonly showCostCols  = false;
  protected readonly showStockCols = false;

  // ── UI state ─────────────────────────────────────────────────────────────
  protected readonly filterFam = signal('all');
  protected readonly sortKey   = signal<SortKey>('revenue');
  protected readonly search    = signal('');
  protected readonly selected  = signal<Enriched | null>(null);

  // ── Chart hover ───────────────────────────────────────────────────────────
  protected readonly hoveredDay = signal<number | null>(null);
  protected readonly tipX       = signal(0);
  protected readonly tipY       = signal(0);

  // ── Matrix hover ──────────────────────────────────────────────────────────
  protected readonly matrixHovered   = signal<MatrixPoint | null>(null);
  protected readonly matrixTipX      = signal(0);
  protected readonly matrixTipY      = signal(0);
  protected readonly matrixWrapWidth = signal(480);

  constructor() {
    effect(() => {
      this.facade.period();
      this.filterFam.set('all');
      this.search.set('');
    });
  }

  // ── Enriched list (ABC + deltas) ──────────────────────────────────────────
  protected readonly enriched = computed((): Enriched[] => {
    const items = this.facade.productsReport()?.items;
    if (!items?.length) return [];

    const total = items.reduce((s, p) => s + p.revenue, 0);
    let cum = 0;
    const abcMap = new Map<string, 'A' | 'B' | 'C'>();
    for (const p of [...items].sort((a, b) => b.revenue - a.revenue)) {
      cum += p.revenue;
      const pct = total > 0 ? cum / total : 1;
      abcMap.set(p.product_uuid, pct <= 0.7 ? 'A' : pct <= 0.9 ? 'B' : 'C');
    }

    return items.map(p => ({
      ...p,
      stock:      999,
      margin:     0,
      mPct:       0,
      days:       Infinity,
      alert:      null,
      abc:        abcMap.get(p.product_uuid) ?? 'C',
      revDelta:   p.prev_revenue > 0 ? ((p.revenue - p.prev_revenue) / p.prev_revenue) * 100 : null,
      unitsDelta: p.prev_units   > 0 ? ((p.units   - p.prev_units)   / p.prev_units)   * 100 : null,
    }));
  });

  protected readonly families = computed(() =>
    ['all', ...Array.from(new Set(this.enriched().map(p => p.family)))]
  );

  protected readonly filtered = computed(() => {
    const fam = this.filterFam();
    const q   = this.search().toLowerCase().trim();
    let list  = this.enriched();
    if (fam !== 'all') list = list.filter(p => p.family === fam);
    if (q)             list = list.filter(p =>
      p.name.toLowerCase().includes(q) || p.family.toLowerCase().includes(q)
    );
    return list;
  });

  protected readonly sorted = computed(() => {
    const key = this.sortKey();
    return [...this.filtered()].sort((a, b) => key === 'units' ? b.units - a.units : b.revenue - a.revenue);
  });

  protected readonly totalRevenue    = computed(() => this.enriched().reduce((s, p) => s + p.revenue, 0));
  protected readonly totalUnits      = computed(() => this.enriched().reduce((s, p) => s + p.units,   0));
  protected readonly familyCount     = computed(() => new Set(this.enriched().map(p => p.family)).size);
  protected readonly filteredRevenue = computed(() => this.filtered().reduce((s, p) => s + p.revenue, 0));

  // ── Matrix — viewBox 0 0 360 300, plot area x:40–340, y:20–260 ───────────
  protected readonly matrixPoints = computed((): MatrixPoint[] => {
    const items = this.enriched();
    if (items.length < 2) return [];
    const maxU = Math.max(...items.map(p => p.units),   1);
    const maxR = Math.max(...items.map(p => p.revenue), 1);
    const medU = this.median(items.map(p => p.units));
    const medR = this.median(items.map(p => p.revenue));
    return items.map(p => ({
      ...p,
      cx:       40  + (p.units   / maxU) * 300,
      cy:       260 - (p.revenue / maxR) * 240,
      quadrant: this.quadrantOf(p.units, p.revenue, medU, medR),
    }));
  });

  protected readonly matrixMedX = computed(() => {
    const items = this.enriched();
    if (!items.length) return 190;
    return 40 + (this.median(items.map(p => p.units)) / Math.max(...items.map(p => p.units), 1)) * 300;
  });

  protected readonly matrixMedY = computed(() => {
    const items = this.enriched();
    if (!items.length) return 140;
    return 260 - (this.median(items.map(p => p.revenue)) / Math.max(...items.map(p => p.revenue), 1)) * 240;
  });

  protected readonly selectedQuadrant = computed(() => {
    const p  = this.selected();
    const pt = this.matrixPoints().find(mp => mp.name === p?.name);
    return pt ? QMETA[pt.quadrant] : null;
  });

  protected onMatrixMove(e: MouseEvent): void {
    const el = e.currentTarget as HTMLElement;
    const r  = el.getBoundingClientRect();
    this.matrixWrapWidth.set(r.width);
    this.matrixTipX.set(e.clientX - r.left);
    this.matrixTipY.set(e.clientY - r.top);
  }

  protected onMatrixLeave(): void { this.matrixHovered.set(null); }

  protected quadrantOf(u: number, r: number, medU: number, medR: number): QuadrantKey {
    if (u >= medU && r >= medR) return 'star';
    if (u < medU  && r >= medR) return 'enigma';
    if (u >= medU && r < medR)  return 'horse';
    return 'dog';
  }

  private median(arr: number[]): number {
    const s = [...arr].sort((a, b) => a - b);
    const m = Math.floor(s.length / 2);
    return s.length % 2 === 0 ? (s[m - 1] + s[m]) / 2 : s[m];
  }

  // ── ABC / Delta helpers ───────────────────────────────────────────────────
  protected abcColor(abc: 'A' | 'B' | 'C'): string {
    return abc === 'A' ? '#1a9e5a' : abc === 'B' ? '#d18a1c' : '#9b9b9b';
  }

  protected deltaColor(v: number | null): string {
    if (v === null) return '#0077cc';
    return v > 2 ? '#1a9e5a' : v < -2 ? '#ff4d4d' : '#7a7a7a';
  }

  protected deltaArrow(v: number | null): string {
    if (v === null) return '●';
    return v > 2 ? '↑' : v < -2 ? '↓' : '→';
  }

  protected fmtDelta(v: number | null): string {
    if (v === null) return 'Nuevo';
    return `${v >= 0 ? '+' : ''}${v.toFixed(1).replace('.', ',')}%`;
  }

  // ── Product modal ──────────────────────────────────────────────────────────
  protected openProduct(p: Enriched): void {
    this.selected.set(p);
    this.hoveredDay.set(null);
  }

  protected closeProduct(): void {
    this.selected.set(null);
    this.hoveredDay.set(null);
  }

  protected onChartMove(e: MouseEvent): void {
    const wrap = e.currentTarget as HTMLElement;
    const svg  = wrap.querySelector('svg') as SVGSVGElement | null;
    if (!svg) return;
    const sr    = svg.getBoundingClientRect();
    const wr    = wrap.getBoundingClientRect();
    const pad   = sr.width * (10 / 520);
    const inner = sr.width - 2 * pad;
    const idx   = Math.min(13, Math.max(0, Math.round(((e.clientX - sr.left - pad) / inner) * 13)));
    this.hoveredDay.set(idx);
    this.tipX.set(e.clientX - wr.left);
    this.tipY.set(e.clientY - wr.top);
  }

  protected onChartLeave(): void { this.hoveredDay.set(null); }

  protected hovX(W = 520, pad = 10): number {
    const i = this.hoveredDay();
    return i !== null ? pad + (i / 13) * (W - 2 * pad) : -1000;
  }

  protected hovY(data: number[], H = 160, pad = 10): number {
    const i = this.hoveredDay();
    if (i === null) return -1000;
    return pad + (1 - data[i] / Math.max(...data, 1)) * (H - 2 * pad);
  }

  // ── SVG chart helpers ─────────────────────────────────────────────────────
  protected chartDates(): string[] {
    const today = new Date();
    return Array.from({ length: 14 }, (_, i) => {
      const d = new Date(today);
      d.setDate(today.getDate() - (13 - i));
      return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
    });
  }

  protected linePath(data: number[], W: number, H: number, pad = 10): string {
    if (!data.length) return '';
    const max = Math.max(...data, 1);
    return data.map((v, i) => {
      const x = pad + (i / (data.length - 1)) * (W - 2 * pad);
      const y = pad + (1 - v / max) * (H - 2 * pad);
      return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
    }).join(' ');
  }

  protected areaPath(data: number[], W: number, H: number, pad = 10): string {
    const line = this.linePath(data, W, H, pad);
    if (!line) return '';
    return `${line} L ${(pad + (data.length - 1) / (data.length - 1) * (W - 2 * pad)).toFixed(1)} ${H - pad} L ${pad.toFixed(1)} ${H - pad} Z`;
  }

  protected gridY(H: number, pad = 10, steps = 4): number[] {
    return Array.from({ length: steps + 1 }, (_, i) => pad + (i / steps) * (H - 2 * pad));
  }

  protected gridX(W: number, pad = 10, n = 14): number[] {
    return Array.from({ length: n }, (_, i) => pad + (i / (n - 1)) * (W - 2 * pad));
  }

  protected chartPoints(data: number[], W: number, H: number, pad = 10): { x: number; y: number; active: boolean }[] {
    const max = Math.max(...data, 1);
    return data.map((v, i) => ({
      x: pad + (i / (data.length - 1)) * (W - 2 * pad),
      y: pad + (1 - v / max) * (H - 2 * pad),
      active: v > 0,
    }));
  }

  // ── Trend (spark) ─────────────────────────────────────────────────────────
  protected trendInfo(spark: number[]): { trend: string; delta: number | null } | null {
    if (!spark || spark.length < 7) return null;
    const recent = spark.slice(-3).reduce((a, b) => a + b, 0) / 3;
    const older  = spark.slice(-7, -4).reduce((a, b) => a + b, 0) / 3;
    if (recent === 0 && older === 0) return null;
    const delta = older > 0 ? ((recent - older) / older) * 100 : null;
    const trend = delta !== null ? (delta > 10 ? 'up' : delta < -10 ? 'down' : 'flat') : 'up';
    return { trend, delta };
  }

  protected sparkPath(data: number[]): string { return this.facade.sparklinePath(data, 50, 20); }
  protected sparkArea(data: number[]): string { return this.facade.sparklineArea(data, 50, 20); }
  protected trendColor(t: string): string     { return t === 'up' ? '#1a9e5a' : t === 'down' ? '#ff4d4d' : '#a0a0a0'; }
  protected trendArrow(t: string): string     { return t === 'up' ? '↗' : t === 'down' ? '↘' : '→'; }

  protected get familyMax(): number {
    return Math.max(...this.facade.byFamily.map(f => f.v), 1);
  }

  // ── CSV ───────────────────────────────────────────────────────────────────
  protected exportCsv(): void {
    const rows   = this.sorted();
    const period = this.facade.periodLabel();
    const header = ['Producto', 'Familia', 'ABC', 'Unidades', 'Ingresos (€)', '% sobre periodo'];
    const lines  = rows.map(p => [
      `"${p.name.replace(/"/g, '""')}"`, `"${p.family.replace(/"/g, '""')}"`,
      p.abc, p.units, (p.revenue / 100).toFixed(2), p.pct.toFixed(2),
    ].join(';'));
    const blob = new Blob(['﻿' + [header.join(';'), ...lines].join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = `productos-${period}-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }

  protected fmt(v: number): string    { return this.facade.fmt(v); }
  protected fmtInt(n: number): string { return this.facade.fmtInt(n); }

  protected readonly sortLabel: Record<SortKey, string> = {
    revenue: 'ingresos', units: 'unidades', margin: 'margen €',
    marginPct: 'margen %', stock: 'stock', rotation: 'rotación',
  };
  protected setSort(key: SortKey): void { this.sortKey.set(key); }
}
