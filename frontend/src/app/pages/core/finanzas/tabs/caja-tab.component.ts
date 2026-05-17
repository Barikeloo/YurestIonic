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

  protected readonly view       = signal<'session' | 'preclose' | 'cancellations'>('session');
  protected readonly movFilter  = signal<'all' | 'in' | 'out'>('all');
  protected readonly cancelCat  = signal<string>('all');

  protected readonly theoretical = this.facade.cashTheoretical;
  protected readonly s = this.facade.cashSession;

  protected readonly filteredMovements = computed(() => {
    const f = this.movFilter();
    return this.s.movements.filter(m => f === 'all' || m.type === f);
  });

  protected readonly filteredCancellations = computed(() => {
    const cat = this.cancelCat();
    return this.facade.cancellations.filter(c => cat === 'all' || c.category === cat);
  });

  protected readonly checklistDone = computed(() =>
    this.facade.preClose.checklist.filter(c => c.done).length
  );
  protected readonly canClose = computed(() =>
    this.facade.preClose.checklist.filter(c => c.blocking && !c.done).length === 0
  );

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
  protected categoryLabel(cat: string): string {
    const map: Record<string, string> = { error: 'Error TPV', queja: 'Queja', fuga: 'Fuga', devolucion: 'Devolución', cancelacion: 'Cancelación', all: 'Todas' };
    return map[cat] || cat;
  }
  protected categoryColor(cat: string): string {
    const map: Record<string, string> = { error: '#0077cc', queja: '#d18a1c', fuga: '#ff4d4d', devolucion: '#7857d6', cancelacion: '#a0a0a0' };
    return map[cat] || '#a0a0a0';
  }
  protected categoryBg(cat: string): string {
    const map: Record<string, string> = { error: '#e8f0fb', queja: '#fbf2dc', fuga: '#ffecec', devolucion: '#f0e8fd', cancelacion: '#f4f4f4' };
    return map[cat] || '#f4f4f4';
  }

  protected sessionBreakdown(): { label: string; value: string; color: string }[] {
    return [
      { label: 'Fondo inicial',    value: this.fmt(this.s.initial),              color: '#5a5a5a' },
      { label: 'Ventas efectivo',  value: this.fmt(this.facade.byMethod.cash.v), color: '#1a9e5a' },
      { label: 'Entradas',         value: `+ ${this.fmt(this.s.cashIn)}`,        color: '#1a9e5a' },
      { label: 'Salidas',          value: `− ${this.fmt(this.s.cashOut)}`,       color: '#ff4d4d' },
    ];
  }

  protected readonly sumAmt = (acc: number, c: { amount: number }): number => acc + c.amount;
}
