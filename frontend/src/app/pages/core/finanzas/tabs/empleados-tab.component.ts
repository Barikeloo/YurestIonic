import { Component, inject, signal, computed } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import { IconComponent } from '../../../../shared/components/icon/icon.component';
import type { EmployeeReportItem } from '../models/finanzas.models';

type SortKey = 'revenue' | 'tickets' | 'avg' | 'items' | 'tips';

type Enriched = EmployeeReportItem & {
  itemsPerTicket: number;
  tipsPct:        number;
  discPct:        number;
  revDelta:       number | null;
  ticketsDelta:   number | null;
  rank:           number;
};

@Component({
  selector: 'app-finanzas-empleados-tab',
  templateUrl: './empleados-tab.component.html',
  styleUrls: ['./empleados-tab.component.scss'],
  standalone: true,
  imports: [IconComponent],
})
export class EmpleadosTabComponent {
  protected readonly facade = inject(FinanzasFacade);

  protected readonly selectedId = signal<string | null>(null);
  protected readonly sortKey    = signal<SortKey>('revenue');
  protected readonly searchTerm = signal('');

  private get items(): EmployeeReportItem[] {
    return this.facade.employeesReport()?.items ?? [];
  }

  // ── Team aggregates ────────────────────────────────────────────────────────
  protected readonly totalRevenue  = computed(() => this.items.reduce((s, e) => s + e.revenue, 0));
  protected readonly totalTickets  = computed(() => this.items.reduce((s, e) => s + e.tickets, 0));
  protected readonly totalTips      = computed(() => this.items.reduce((s, e) => s + e.tips, 0));
  protected readonly totalItems     = computed(() => this.items.reduce((s, e) => s + e.items_sold, 0));
  protected readonly totalDiscount  = computed(() => this.items.reduce((s, e) => s + e.discounts, 0));

  protected readonly teamAvgTicket = computed(() => {
    const t = this.totalTickets();
    return t > 0 ? this.totalRevenue() / t : 0;
  });
  protected readonly teamItemsPerTicket = computed(() => {
    const t = this.totalTickets();
    return t > 0 ? this.totalItems() / t : 0;
  });
  protected readonly teamTipsPct = computed(() => {
    const r = this.totalRevenue();
    return r > 0 ? (this.totalTips() / r) * 100 : 0;
  });
  protected readonly teamDiscPct = computed(() => {
    const r = this.totalRevenue();
    return r > 0 ? (this.totalDiscount() / r) * 100 : 0;
  });

  // ── Enriched list (rank by revenue, ratios, deltas) ───────────────────────
  protected readonly enriched = computed((): Enriched[] => {
    const byRevenue = [...this.items].sort((a, b) => b.revenue - a.revenue);
    const rankMap = new Map<string, number>();
    byRevenue.forEach((e, i) => rankMap.set(e.user_uuid, i + 1));

    return this.items.map(e => ({
      ...e,
      itemsPerTicket: e.tickets > 0 ? e.items_sold / e.tickets : 0,
      tipsPct:        e.revenue > 0 ? (e.tips / e.revenue) * 100 : 0,
      discPct:        e.revenue > 0 ? (e.discounts / e.revenue) * 100 : 0,
      revDelta:       e.prev_revenue > 0 ? ((e.revenue - e.prev_revenue) / e.prev_revenue) * 100 : null,
      ticketsDelta:   e.prev_tickets > 0 ? ((e.tickets - e.prev_tickets) / e.prev_tickets) * 100 : null,
      rank:           rankMap.get(e.user_uuid) ?? 0,
    }));
  });

  protected readonly sortedEmps = computed((): Enriched[] => {
    const key = this.sortKey();
    return [...this.enriched()].sort((a, b) => {
      if (key === 'tickets') return b.tickets - a.tickets;
      if (key === 'avg')     return b.avg_ticket - a.avg_ticket;
      if (key === 'items')   return b.itemsPerTicket - a.itemsPerTicket;
      if (key === 'tips')    return b.tips - a.tips;
      return b.revenue - a.revenue;
    });
  });

  protected readonly filteredEmps = computed((): Enriched[] => {
    const term = this.searchTerm().toLowerCase().trim();
    return term ? this.sortedEmps().filter(e => e.name.toLowerCase().includes(term)) : this.sortedEmps();
  });

  protected readonly selectedEmp = computed((): Enriched | null =>
    this.enriched().find(e => e.user_uuid === this.selectedId()) ?? null
  );

  protected tipsRatioPct(): string {
    return this.teamTipsPct().toFixed(1).replace('.', ',');
  }

  // ── Benchmark vs team (higher is better) ──────────────────────────────────
  protected benchColor(value: number, mean: number): string {
    if (mean <= 0) return '#7a7a7a';
    if (value >= mean)        return '#1a9e5a';
    if (value < mean * 0.8)   return '#ff4d4d';
    return '#d18a1c';
  }
  protected benchLabel(value: number, mean: number): string {
    if (mean <= 0) return '—';
    const pct = ((value - mean) / mean) * 100;
    return `${pct >= 0 ? '+' : ''}${pct.toFixed(0)}% vs equipo`;
  }
  protected isHighDiscounter(e: Enriched): boolean {
    const team = this.teamDiscPct();
    return team > 0 && e.discPct > team * 1.5 && e.discounts > 0;
  }

  // ── Delta (vs previous period) ────────────────────────────────────────────
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

  // ── Spark / format helpers ────────────────────────────────────────────────
  protected sparkPath(data: number[]): string   { return this.facade.sparklinePath(data, 100, 28); }
  protected sparkArea(data: number[]): string   { return this.facade.sparklineArea(data, 100, 28); }
  protected sparkPathLg(data: number[]): string { return this.facade.sparklinePath(data, 100, 60); }
  protected sparkAreaLg(data: number[]): string { return this.facade.sparklineArea(data, 100, 60); }
  protected fmt(v: number): string              { return this.facade.fmt(v); }
  protected fmtInt(n: number): string           { return this.facade.fmtInt(n); }

  protected tipsRatio(e: Enriched): string  { return e.tipsPct.toFixed(1).replace('.', ','); }
  protected discRatio(e: Enriched): string  { return e.discPct.toFixed(1).replace('.', ','); }
  protected discRatioNum(e: Enriched): number { return e.discPct; }

  protected select(uuid: string): void  { this.selectedId.set(uuid); }
  protected setSort(key: SortKey): void { this.sortKey.set(key); }
}
