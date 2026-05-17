import { Component, inject, signal, computed } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import type { QuarterVat } from '../models/finanzas.models';

@Component({
  selector: 'app-finanzas-impuestos-tab',
  templateUrl: './impuestos-tab.component.html',
  styleUrls: ['./impuestos-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class ImpuestosTabComponent {
  protected readonly facade = inject(FinanzasFacade);

  protected readonly activeQ = signal<'T1' | 'T2' | 'T3' | 'T4'>('T2');
  protected readonly show303 = signal(false);
  protected readonly copied  = signal<string | null>(null);

  protected readonly q = computed<QuarterVat>(() => this.facade.quarterly[this.activeQ()]);

  // Period (today) totals — static since breakdown is static mock
  protected readonly breakdown  = this.facade.taxBreakdown;
  protected readonly totalBase  = this.breakdown.reduce((s, t) => s + t.base, 0);
  protected readonly totalTax   = this.breakdown.reduce((s, t) => s + t.tax, 0);
  protected readonly totalGross = this.totalBase + this.totalTax;

  // Quarterly totals (reactive)
  protected readonly qTotalTax = computed(() => {
    const d = this.q();
    return d.tax4 + d.tax10 + d.tax21;
  });
  protected readonly qTotalBase = computed(() => {
    const d = this.q();
    return d.base4 + d.base10 + d.base21;
  });

  // 3-col cards in Modelo 303 section
  protected readonly m303Rates = computed(() => {
    const d = this.q();
    return [
      { rate: 4,  base: d.base4,  tax: d.tax4,  casillas: ['01', '03'], color: '#1a9e5a' },
      { rate: 10, base: d.base10, tax: d.tax10, casillas: ['04', '06'], color: '#0077cc' },
      { rate: 21, base: d.base21, tax: d.tax21, casillas: ['07', '09'], color: '#ff4d4d' },
    ];
  });

  // Rows for faux PDF table in modal
  protected readonly pdfRows = computed(() => {
    const d = this.q();
    return [
      { casillas: ['01', '02', '03'], concepto: 'Régimen general 4%',  base: d.base4,  rate: 4,  cuota: d.tax4  },
      { casillas: ['04', '05', '06'], concepto: 'Régimen general 10%', base: d.base10, rate: 10, cuota: d.tax10 },
      { casillas: ['07', '08', '09'], concepto: 'Régimen general 21%', base: d.base21, rate: 21, cuota: d.tax21 },
    ];
  });

  protected readonly colorByRate: Record<number, string> = {
    4: '#1a9e5a', 10: '#0077cc', 21: '#ff4d4d',
  };

  protected fmt(v: number): string { return this.facade.fmt(v); }

  protected barPct(total: number): number {
    return this.totalGross > 0 ? (total / this.totalGross) * 100 : 0;
  }

  protected copyField(key: string, value: number): void {
    const text = (value / 100).toFixed(2).replace('.', ',');
    navigator.clipboard?.writeText(text);
    this.copied.set(key);
    setTimeout(() => this.copied.set(null), 1500);
  }

  protected setQ(q: 'T1' | 'T2' | 'T3' | 'T4'): void { this.activeQ.set(q); }
}
