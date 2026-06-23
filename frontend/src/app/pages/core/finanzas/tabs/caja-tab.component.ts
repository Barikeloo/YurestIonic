import { Component, inject, signal, computed } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';

@Component({
  selector: 'app-finanzas-caja-tab',
  templateUrl: './caja-tab.component.html',
  styleUrls: ['./caja-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class CajaTabComponent {
  protected readonly facade = inject(FinanzasFacade);

  protected readonly movFilter = signal<'all' | 'in' | 'out'>('all');

  protected readonly s          = this.facade.cashSession;
  protected readonly theoretical = this.facade.cashTheoretical;

  protected readonly modalOpen = signal(false);
  protected readonly modalMovFilter = signal<'all' | 'in' | 'out'>('all');


  protected readonly filteredMovements = computed(() => {
    const session = this.s();
    if (!session) return [];
    const f = this.movFilter();
    return session.movements.filter(m => f === 'all' || m.type === f);
  });

  protected readonly modalFilteredMovements = computed(() => {
    const movements = this.facade.selectedSessionMovements();
    const f = this.modalMovFilter();
    return movements.filter(m => f === 'all' || m.type === f);
  });

  protected readonly modalSession = computed(() => {
    const item = this.facade.selectedSessionItem();
    const summary = this.facade.selectedSessionSummary();
    if (!item) return null;
    return {
      item,
      summary,
      movements: this.facade.selectedSessionMovements(),
    };
  });


  protected openDetail(uuid: string): void {
    this.facade.loadSessionDetail(uuid);
    this.modalOpen.set(true);
  }

  protected closeDetail(): void {
    this.modalOpen.set(false);
  }


  protected fmt(v: number): string { return this.facade.fmt(v); }
  protected fmtInt(n: number): string { return this.facade.fmtInt(n); }

  protected diffColor(diff: number): string {
    if (diff === 0) return '#1a9e5a';
    if (Math.abs(diff) < 500) return '#d18a1c';
    return '#ff4d4d';
  }
  protected diffBg(diff: number): string {
    if (diff === 0) return '#e8f7ef';
    if (Math.abs(diff) < 500) return '#fbf2dc';
    return '#ffecec';
  }

  protected formatDateTime(dt: string | null): string {
    if (!dt) return '—';
    return this.facade.formatDateTime(dt);
  }

  protected sessionBreakdown(): { label: string; value: string; color: string }[] {
    const session = this.s();
    const cashSales = this.facade.cashSummary()?.total_cash_payments ?? 0;
    return [
      { label: 'Fondo inicial',    value: this.fmt(session?.initial ?? 0),        color: '#5a5a5a' },
      { label: 'Ventas efectivo',  value: this.fmt(cashSales),                    color: '#1a9e5a' },
      { label: 'Entradas',         value: `+ ${this.fmt(session?.cashIn ?? 0)}`,  color: '#1a9e5a' },
      { label: 'Salidas',          value: `− ${this.fmt(session?.cashOut ?? 0)}`, color: '#ff4d4d' },
    ];
  }

  protected readonly sumAmt = (acc: number, c: { amount: number }): number => acc + c.amount;
}
