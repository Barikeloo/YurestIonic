import { Component, inject, signal, computed, effect } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import type { ProductRanking } from '../models/finanzas.models';

type SortKey = 'revenue' | 'units' | 'margin' | 'marginPct' | 'stock' | 'rotation';

type Enriched = ProductRanking & {
  margin:      number;
  mPct:        number;
  days:        number;
  alert:       'critical' | 'low' | null;
  trendSpark:  number[];
  familyColor: string;
};

@Component({
  selector: 'app-finanzas-productos-tab',
  templateUrl: './productos-tab.component.html',
  styleUrls: ['./productos-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class ProductosTabComponent {
  protected readonly facade = inject(FinanzasFacade);

  // ── MVP feature flags ─────────────────────────────────────────────────────
  protected readonly showCostCols  = false; // no cost data until cost module is implemented
  protected readonly showStockCols = false; // no stock data until inventory module is implemented

  protected readonly filterFam = signal('all');
  protected readonly sortKey   = signal<SortKey>('revenue');

  constructor() {
    effect(() => {
      this.facade.period(); // react to period changes
      this.filterFam.set('all');
    });
  }

  protected readonly enriched = computed((): Enriched[] => {
    const items = this.facade.productsReport()?.items;

    if (items) {
      return items.map(p => ({
        name: p.name, family: p.family, units: p.units, revenue: p.revenue,
        cost: p.cost, price: p.price, avgDaily: p.avg_daily, stock: 999, pct: p.pct,
        margin: 0, mPct: 0, days: Infinity, alert: null,
        trendSpark:  p.trend_spark,
        familyColor: p.family_color,
      }));
    }

    return this.facade.productRanking.map(p => ({
      ...p, margin: 0, mPct: 0, days: Infinity, alert: null,
      trendSpark: [], familyColor: '#7a7a7a',
    }));
  });

  protected readonly families = computed(() =>
    ['all', ...Array.from(new Set(this.enriched().map(p => p.family)))]
  );

  protected readonly filtered = computed(() => {
    const fam = this.filterFam();
    const list = this.enriched();
    return fam === 'all' ? list : list.filter(p => p.family === fam);
  });

  protected readonly sorted = computed(() => {
    const key = this.sortKey();
    return [...this.filtered()].sort((a, b) => {
      if (key === 'units') return b.units - a.units;
      return b.revenue - a.revenue;
    });
  });

  protected readonly totalRevenue   = computed(() => this.enriched().reduce((s, p) => s + p.revenue, 0));
  protected readonly totalUnits     = computed(() => this.enriched().reduce((s, p) => s + p.units, 0));
  protected readonly familyCount    = computed(() => new Set(this.enriched().map(p => p.family)).size);
  protected readonly filteredRevenue = computed(() => this.filtered().reduce((s, p) => s + p.revenue, 0));

  // ── Trend: compare avg last 3 days vs avg days 4–6 ago ───────────────────
  protected trendInfo(spark: number[]): { trend: string; delta: number | null; spark: number[] } | null {
    if (!spark || spark.length < 7) return null;
    const recent = spark.slice(-3).reduce((a, b) => a + b, 0) / 3;
    const older  = spark.slice(-7, -4).reduce((a, b) => a + b, 0) / 3;
    if (recent === 0 && older === 0) return null;
    const delta = older > 0 ? ((recent - older) / older) * 100 : null;
    const trend = delta !== null
      ? (delta > 10 ? 'up' : delta < -10 ? 'down' : 'flat')
      : (recent > 0 ? 'up' : 'flat');
    return { trend, delta, spark };
  }

  protected sparkPath(data: number[]): string { return this.facade.sparklinePath(data, 50, 20); }
  protected sparkArea(data: number[]): string { return this.facade.sparklineArea(data, 50, 20); }
  protected trendColor(t: string): string { return t === 'up' ? '#1a9e5a' : t === 'down' ? '#ff4d4d' : '#a0a0a0'; }
  protected trendArrow(t: string): string { return t === 'up' ? '↗' : t === 'down' ? '↘' : '→'; }

  // ── Family chart ──────────────────────────────────────────────────────────
  protected get familyMax(): number {
    return Math.max(...this.facade.byFamily.map(f => f.v), 1);
  }

  // ── CSV export ────────────────────────────────────────────────────────────
  protected exportCsv(): void {
    const rows   = this.sorted();
    const period = this.facade.periodLabel();
    const header = ['Producto', 'Familia', 'Unidades', 'Ingresos (€)', '% sobre periodo'];
    const lines  = rows.map(p => [
      `"${p.name.replace(/"/g, '""')}"`,
      `"${p.family.replace(/"/g, '""')}"`,
      p.units,
      (p.revenue / 100).toFixed(2),
      p.pct.toFixed(2),
    ].join(';'));
    const csv  = [header.join(';'), ...lines].join('\n');
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
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
