import { Component, computed, inject, signal } from '@angular/core';
import { FinanzasFacade } from '../facades/finanzas.facade';
import type { Quarter } from '../models/finanzas.models';

const RATE_META: Record<number, { casillas: string[]; color: string; pdfCasillas: string[] }> = {
  4:  { casillas: ['01', '03'], color: '#1a9e5a', pdfCasillas: ['01', '02', '03'] },
  10: { casillas: ['04', '06'], color: '#0077cc', pdfCasillas: ['04', '05', '06'] },
  21: { casillas: ['07', '09'], color: '#ff4d4d', pdfCasillas: ['07', '08', '09'] },
};

const RATE_CONCEPTO: Record<number, string> = {
  4:  'Régimen general 4%',
  10: 'Régimen general 10%',
  21: 'Régimen general 21%',
};

@Component({
  selector: 'app-finanzas-impuestos-tab',
  templateUrl: './impuestos-tab.component.html',
  styleUrls: ['./impuestos-tab.component.scss'],
  standalone: true,
  imports: [],
})
export class ImpuestosTabComponent {
  protected readonly facade  = inject(FinanzasFacade);
  protected readonly show303 = signal(false);
  protected readonly copied  = signal<string | null>(null);

  protected readonly colorByRate: Record<number, string> = {
    4: '#1a9e5a', 10: '#0077cc', 21: '#ff4d4d',
  };

  protected readonly breakdown  = computed(() => this.facade.taxReport()?.breakdown ?? []);
  protected readonly totalBase  = computed(() => this.breakdown().reduce((s, t) => s + t.base, 0));
  protected readonly totalTax   = computed(() => this.breakdown().reduce((s, t) => s + t.tax,  0));
  protected readonly totalGross = computed(() => this.totalBase() + this.totalTax());

  protected readonly q          = computed(() => this.facade.taxReport()?.quarterly);
  protected readonly restaurant = computed(() => this.facade.taxReport()?.restaurant);

  protected readonly qTotalTax  = computed(() => (this.q()?.rates ?? []).reduce((s, r) => s + r.tax,  0));
  protected readonly qTotalBase = computed(() => (this.q()?.rates ?? []).reduce((s, r) => s + r.base, 0));

  protected readonly m303Rates = computed(() =>
    (this.q()?.rates ?? []).map(r => ({
      ...r,
      casillas: RATE_META[r.rate]?.casillas ?? ['—'],
      color:    RATE_META[r.rate]?.color    ?? '#5a5a5a',
    }))
  );

  protected readonly pdfRows = computed(() =>
    (this.q()?.rates ?? []).map(r => ({
      casillas: RATE_META[r.rate]?.pdfCasillas ?? ['—'],
      concepto: RATE_CONCEPTO[r.rate] ?? `IVA ${r.rate}%`,
      base:     r.base,
      rate:     r.rate,
      cuota:    r.tax,
    }))
  );

  protected fmt(v: number): string { return this.facade.fmt(v); }

  protected barPct(total: number): number {
    const g = this.totalGross();
    return g > 0 ? (total / g) * 100 : 0;
  }

  protected copyField(key: string, value: number): void {
    const text = (value / 100).toFixed(2).replace('.', ',');
    navigator.clipboard?.writeText(text);
    this.copied.set(key);
    setTimeout(() => this.copied.set(null), 1500);
  }

  protected setQ(q: Quarter): void { this.facade.setActiveQ(q); }

  protected downloadPdf(): void { this.facade.downloadTaxPdf(); }

  protected sendToGestor(): void {
    const email = prompt('Email del gestor:');
    if (email && email.includes('@')) {
      this.facade.sendTaxPdf(email);
    }
  }
}
