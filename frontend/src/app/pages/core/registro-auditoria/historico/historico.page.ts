import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { Router } from '@angular/router';
import { HistoricoFacade } from './facades/historico.facade';

@Component({
  selector: 'app-historico',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './historico.page.html',
  styleUrls: ['./historico.page.scss'],
  providers: [HistoricoFacade],
})
export class HistoricoPage implements OnInit, OnDestroy {
  protected readonly facade = inject(HistoricoFacade);
  private readonly router = inject(Router);

  // Facade signal proxies
  get stats() { return this.facade.stats; }
  get isLoading() { return this.facade.isLoading; }
  get loadError() { return this.facade.loadError; }
  get lastUpdatedAt() { return this.facade.lastUpdatedAt; }
  get hasData() { return this.facade.hasData; }
  get total() { return this.facade.total; }
  get oldestDate() { return this.facade.oldestDate; }
  get newestDate() { return this.facade.newestDate; }
  get rangeLabel() { return this.facade.rangeLabel; }
  get spanInMonths() { return this.facade.spanInMonths; }
  get monthlyPoints() { return this.facade.monthlyPoints; }
  get peakMonth() { return this.facade.peakMonth; }
  get monthlyAverage() { return this.facade.monthlyAverage; }

  ngOnInit(): void {
    this.facade.loadStats();
  }

  ngOnDestroy(): void {
    this.facade.ngOnDestroy();
  }

  reload(): void { this.facade.loadStats(); }

  goBackToRegistro(): void { this.router.navigate(['/registro-auditoria']); }

  openRegistroWithHistorico(): void {
    this.router.navigate(['/registro-auditoria'], { queryParams: { historico: 1 } });
  }

  formatNumber(n: number): string { return n.toLocaleString('es-ES'); }

  formatDateLong(d: Date | null): string {
    if (!d) return '—';
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  formatLastUpdated(d: Date | null): string {
    if (!d) return '';
    return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
  }
}
