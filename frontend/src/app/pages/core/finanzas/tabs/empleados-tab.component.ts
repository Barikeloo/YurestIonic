import { Component, inject, signal, computed } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import type { Employee } from '../models/finanzas.models';

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

  protected readonly selectedId = signal<string>('mg');
  protected readonly sortKey    = signal<SortKey>('revenue');

  protected readonly sortedEmps = computed(() => {
    const key = this.sortKey();
    return [...this.facade.employees].sort((a, b) => {
      if (key === 'tickets') return b.tickets - a.tickets;
      if (key === 'avg')     return b.avgTicket - a.avgTicket;
      if (key === 'tips')    return b.tips - a.tips;
      return b.revenue - a.revenue;
    });
  });

  protected readonly selectedEmp = computed(() =>
    this.facade.employees.find(e => e.id === this.selectedId()) ?? null
  );

  protected readonly totalRevenue  = computed(() => this.facade.employees.reduce((s, e) => s + e.revenue, 0));
  protected readonly totalTickets  = computed(() => this.facade.employees.reduce((s, e) => s + e.tickets, 0));
  protected readonly totalTips     = computed(() => this.facade.employees.reduce((s, e) => s + e.tips, 0));
  protected readonly totalDiscount = computed(() => this.facade.employees.reduce((s, e) => s + e.discounts, 0));
  protected readonly activeCount   = computed(() => this.facade.employees.filter(e => e.active).length);

  protected tipsRatioPct(): string {
    const r = this.totalRevenue();
    return r > 0 ? (this.totalTips() / r * 100).toFixed(1).replace('.', ',') : '0,0';
  }

  protected sparkPath(data: number[]): string       { return this.facade.sparklinePath(data, 100, 28); }
  protected sparkArea(data: number[]): string       { return this.facade.sparklineArea(data, 100, 28); }
  protected sparkPathLg(data: number[]): string     { return this.facade.sparklinePath(data, 100, 60); }
  protected sparkAreaLg(data: number[]): string     { return this.facade.sparklineArea(data, 100, 60); }

  protected fmt(v: number): string { return this.facade.fmt(v); }

  protected tipsRatio(e: Employee): string {
    return (e.revenue > 0 ? (e.tips / e.revenue) * 100 : 0).toFixed(1).replace('.', ',');
  }
  protected discRatio(e: Employee): string {
    return (e.revenue > 0 ? (e.discounts / e.revenue) * 100 : 0).toFixed(1).replace('.', ',');
  }
  protected discRatioNum(e: Employee): number {
    return e.revenue > 0 ? (e.discounts / e.revenue) * 100 : 0;
  }

  protected select(id: string): void { this.selectedId.set(id); }
  protected setSort(key: SortKey): void { this.sortKey.set(key); }
}
