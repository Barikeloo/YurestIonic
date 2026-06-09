import { Component, inject, signal, computed } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import type { EmployeeReportItem } from '../models/finanzas.models';

type SortKey = 'revenue' | 'tickets' | 'avg' | 'tips';

@Component({
  selector: 'app-finanzas-empleados-tab',
  templateUrl: './empleados-tab.component.html',
  styleUrls: ['./empleados-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class EmpleadosTabComponent {
  protected readonly facade = inject(FinanzasFacade);

  protected readonly selectedId = signal<string | null>(null);
  protected readonly sortKey    = signal<SortKey>('revenue');

  private get items(): EmployeeReportItem[] {
    return this.facade.employeesReport()?.items ?? [];
  }

  protected readonly sortedEmps = computed((): EmployeeReportItem[] => {
    const key = this.sortKey();
    return [...this.items].sort((a, b) => {
      if (key === 'tickets') return b.tickets - a.tickets;
      if (key === 'avg')     return b.avg_ticket - a.avg_ticket;
      if (key === 'tips')    return b.tips - a.tips;
      return b.revenue - a.revenue;
    });
  });

  protected readonly selectedEmp = computed((): EmployeeReportItem | null =>
    this.items.find(e => e.user_uuid === this.selectedId()) ?? null
  );

  protected readonly totalRevenue  = computed(() => this.items.reduce((s, e) => s + e.revenue, 0));
  protected readonly totalTickets  = computed(() => this.items.reduce((s, e) => s + e.tickets, 0));
  protected readonly totalTips     = computed(() => this.items.reduce((s, e) => s + e.tips, 0));
  protected readonly totalDiscount = computed(() => this.items.reduce((s, e) => s + e.discounts, 0));

  protected tipsRatioPct(): string {
    const r = this.totalRevenue();
    return r > 0 ? (this.totalTips() / r * 100).toFixed(1).replace('.', ',') : '0,0';
  }

  protected sparkPath(data: number[]): string   { return this.facade.sparklinePath(data, 100, 28); }
  protected sparkArea(data: number[]): string   { return this.facade.sparklineArea(data, 100, 28); }
  protected sparkPathLg(data: number[]): string { return this.facade.sparklinePath(data, 100, 60); }
  protected sparkAreaLg(data: number[]): string { return this.facade.sparklineArea(data, 100, 60); }
  protected fmt(v: number): string              { return this.facade.fmt(v); }
  protected fmtInt(n: number): string           { return this.facade.fmtInt(n); }

  protected tipsRatio(e: EmployeeReportItem): string {
    return (e.revenue > 0 ? (e.tips / e.revenue) * 100 : 0).toFixed(1).replace('.', ',');
  }
  protected discRatio(e: EmployeeReportItem): string {
    return (e.revenue > 0 ? (e.discounts / e.revenue) * 100 : 0).toFixed(1).replace('.', ',');
  }
  protected discRatioNum(e: EmployeeReportItem): number {
    return e.revenue > 0 ? (e.discounts / e.revenue) * 100 : 0;
  }

  protected select(uuid: string): void  { this.selectedId.set(uuid); }
  protected setSort(key: SortKey): void { this.sortKey.set(key); }
}
