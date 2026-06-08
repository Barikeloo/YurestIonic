import { Component, inject, signal, computed } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import type { ProductRanking, ZoneData } from '../models/finanzas.models';

type SortKey = 'revenue' | 'units' | 'margin' | 'marginPct' | 'stock' | 'rotation';

type Enriched = ProductRanking & {
  margin: number;
  mPct:   number;
  days:   number;
  alert:  'critical' | 'low' | null;
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

  protected readonly filterFam  = signal('all');
  protected readonly showAlerts = signal(false);
  protected readonly sortKey    = signal<SortKey>('revenue');
  protected readonly hovered    = signal<string | null>(null);

  protected readonly enriched = computed((): Enriched[] =>
    this.facade.productRanking.map(p => {
      const margin = (p.price - p.cost) * p.units;
      const mPct   = p.price > 0 ? ((p.price - p.cost) / p.price) * 100 : 0;
      const days   = p.stock >= 999 ? Infinity : p.avgDaily > 0 ? p.stock / p.avgDaily : Infinity;
      const alert  = days < 2 ? 'critical' as const : days < 5 ? 'low' as const : null;
      return { ...p, margin, mPct, days, alert };
    })
  );

  protected readonly families = computed(() =>
    ['all', ...Array.from(new Set(this.enriched().map(p => p.family)))]
  );

  protected readonly filtered = computed(() => {
    const fam  = this.filterFam();
    const only = this.showAlerts();
    let list   = this.enriched();
    if (fam !== 'all') list = list.filter(p => p.family === fam);
    if (only)          list = list.filter(p => p.alert !== null);
    return list;
  });

  protected readonly sorted = computed(() => {
    const key = this.sortKey();
    return [...this.filtered()].sort((a, b) => {
      if (key === 'units')     return b.units  - a.units;
      if (key === 'margin')    return b.margin - a.margin;
      if (key === 'marginPct') return b.mPct   - a.mPct;
      if (key === 'stock')     return a.stock  - b.stock;
      if (key === 'rotation')  return a.days   - b.days;
      return b.revenue - a.revenue;
    });
  });

  protected readonly totalRevenue  = computed(() => this.enriched().reduce((s, p) => s + p.revenue, 0));
  protected readonly totalCost     = computed(() => this.enriched().reduce((s, p) => s + p.cost * p.units, 0));
  protected readonly totalMargin   = computed(() => this.totalRevenue() - this.totalCost());
  protected readonly overallPct    = computed(() => {
    const r = this.totalRevenue(); return r > 0 ? (this.totalMargin() / r) * 100 : 0;
  });
  protected readonly criticalCount = computed(() => this.enriched().filter(p => p.alert === 'critical').length);
  protected readonly lowCount      = computed(() => this.enriched().filter(p => p.alert === 'low').length);
  protected readonly deadCount     = this.facade.deadStock.length;
  protected readonly criticalList  = computed(() => this.enriched().filter(p => p.alert).slice(0, 5));
  protected readonly filteredMargin = computed(() => this.filtered().reduce((s, p) => s + p.margin, 0));
  protected readonly bestMarginNames = computed(() =>
    [...this.enriched()].sort((a, b) => b.margin - a.margin).slice(0, 3).map(p => p.name)
  );

  // ── Zones ─────────────────────────────────────────────────────────────────────
  protected readonly zoneMaxRev  = Math.max(...this.facade.zonesLayout.map(z => z.revenue), 1);
  protected readonly zoneTotal   = this.facade.zonesLayout.reduce((s, z) => s + z.revenue, 0);
  protected readonly zonesSorted = computed(() =>
    [...this.facade.zonesLayout].sort((a, b) => b.revenue - a.revenue)
  );

  protected zoneIntensity(zone: ZoneData): string {
    const t = zone.revenue / this.zoneMaxRev;
    const opacity = (0.15 + t * 0.75).toFixed(2);
    return `rgba(255, 77, 77, ${opacity})`;
  }
  protected zoneTextColor(zone: ZoneData): string {
    return zone.revenue / this.zoneMaxRev > 0.5 ? '#fff' : '#0d0d0d';
  }
  protected zoneRevPct(zone: ZoneData): number {
    return Math.round(zone.revenue / this.zoneTotal * 100);
  }
  protected zoneOccColor(zone: ZoneData): string {
    return zone.occupancy > 75 ? '#1a9e5a' : zone.occupancy > 40 ? '#d18a1c' : '#ff4d4d';
  }

  // ── Family chart ──────────────────────────────────────────────────────────────
  protected readonly familyMax = Math.max(...this.facade.byFamily.map(f => f.v), 1);
  protected familyColor(family: string): string {
    return this.facade.byFamily.find(f => f.label === family)?.color ?? '#7a7a7a';
  }

  protected fmt(v: number): string    { return this.facade.fmt(v); }
  protected fmtInt(n: number): string { return this.facade.fmtInt(n); }

  protected daysStr(days: number): string {
    return isFinite(days) ? days.toFixed(1).replace('.', ',') : '—';
  }
  protected isInfinite(days: number): boolean { return !isFinite(days); }
  protected marginBarColor(pct: number): string {
    return pct >= 60 ? '#1a9e5a' : pct >= 40 ? '#d18a1c' : '#ff4d4d';
  }
  protected stockBarPct(stock: number): number { return Math.min(stock / 50 * 100, 100); }
  protected stockColor(alert: 'critical' | 'low' | null): string {
    return alert === 'critical' ? '#ff4d4d' : alert === 'low' ? '#d18a1c' : '#1a9e5a';
  }

  // ── Trend ─────────────────────────────────────────────────────────────────────
  protected trendInfo(name: string): { trend: string; delta: number; spark: number[] } | null {
    return this.facade.productTrends[name] ?? null;
  }
  protected sparkPath(data: number[]): string { return this.facade.sparklinePath(data, 50, 20); }
  protected sparkArea(data: number[]): string { return this.facade.sparklineArea(data, 50, 20); }
  protected trendColor(t: string): string { return t === 'up' ? '#1a9e5a' : t === 'down' ? '#ff4d4d' : '#a0a0a0'; }
  protected trendArrow(t: string): string { return t === 'up' ? '↗' : t === 'down' ? '↘' : '→'; }

  protected readonly sortLabel: Record<SortKey, string> = {
    revenue: 'ingresos', units: 'unidades', margin: 'margen €',
    marginPct: 'margen %', stock: 'stock', rotation: 'rotación',
  };
  protected setSort(key: SortKey): void { this.sortKey.set(key); }
}
