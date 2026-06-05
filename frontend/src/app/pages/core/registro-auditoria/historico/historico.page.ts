import { CommonModule } from '@angular/common';
import { Component, HostListener, OnDestroy, OnInit, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { HistoricoFacade, RANGE_PRESETS, RangePreset } from './facades/historico.facade';

@Component({
  selector: 'app-historico',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './historico.page.html',
  styleUrls: ['./historico.page.scss'],
  providers: [HistoricoFacade],
})
export class HistoricoPage implements OnInit, OnDestroy {
  protected readonly facade = inject(HistoricoFacade);
  private readonly router = inject(Router);

  readonly RANGE_PRESETS = RANGE_PRESETS;

  // Local model bound to the custom-range date inputs. Applied on the
  // "Aplicar" button; doesn't reload until the user confirms.
  customFrom = '';
  customTo = '';

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
  get exportMenuOpen() { return this.facade.exportMenuOpen; }
  get csvExportUrl() { return this.facade.csvExportUrl; }
  get ndjsonExportUrl() { return this.facade.ndjsonExportUrl; }
  get rangeMenuOpen() { return this.facade.rangeMenuOpen; }
  get activePreset() { return this.facade.activePreset; }
  get dateFrom() { return this.facade.dateFrom; }
  get dateTo() { return this.facade.dateTo; }
  get hasActiveRange() { return this.facade.hasActiveRange; }
  get activeRangeLabel() { return this.facade.activeRangeLabel; }
  get verifyState() { return this.facade.verifyState; }
  get verifyResult() { return this.facade.verifyResult; }
  get verifyError() { return this.facade.verifyError; }
  get categoriesBreakdown() { return this.facade.categoriesBreakdown; }
  get topUsers() { return this.facade.topUsers; }
  get anomalies() { return this.facade.anomalies; }
  get totalAnomalies() { return this.facade.totalAnomalies; }

  ngOnInit(): void {
    this.facade.loadStats();
    this.facade.loadLatestVerify();
  }

  runVerify(): void { this.facade.runVerify(); }

  formatVerifiedAt(d: Date): string {
    const diffMs = Date.now() - d.getTime();
    const mins = Math.floor(diffMs / 60000);
    if (mins < 1) return 'justo ahora';
    if (mins < 60) return `hace ${mins} min`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `hace ${hours} h`;
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  @HostListener('document:keydown.escape')
  onEsc(): void {
    this.facade.closeExportMenu();
    this.facade.closeRangeMenu();
  }

  toggleExportMenu(event: MouseEvent): void {
    event.stopPropagation();
    this.facade.toggleExportMenu();
  }

  closeExportMenu(): void { this.facade.closeExportMenu(); }

  toggleRangeMenu(event: MouseEvent): void {
    event.stopPropagation();
    this.customFrom = this.facade.dateFrom() ?? '';
    this.customTo = this.facade.dateTo() ?? '';
    this.facade.toggleRangeMenu();
  }

  closeRangeMenu(): void { this.facade.closeRangeMenu(); }

  applyPreset(preset: RangePreset): void { this.facade.applyPreset(preset); }

  applyCustomRange(): void {
    this.facade.applyCustomRange(this.customFrom || null, this.customTo || null);
  }

  clearRange(): void { this.facade.clearRange(); }

  ngOnDestroy(): void {
    this.facade.ngOnDestroy();
  }

  reload(): void { this.facade.loadStats(); }

  goBackToRegistro(): void { this.router.navigate(['/registro-auditoria']); }

  openRegistroWithHistorico(): void {
    const from = this.facade.dateFrom() ?? this.toIsoDate(this.facade.oldestDate());
    const to = this.facade.dateTo() ?? this.toIsoDate(this.facade.newestDate());
    const queryParams: Record<string, string | number> = { historico: 1 };
    if (from) queryParams['dateFrom'] = from;
    if (to) queryParams['dateTo'] = to;
    this.router.navigate(['/registro-auditoria'], { queryParams });
  }

  drillDownToMonth(monthKey: string): void {
    const [yearStr, monthStr] = monthKey.split('-');
    const year = parseInt(yearStr, 10);
    const month = parseInt(monthStr, 10);
    if (Number.isNaN(year) || Number.isNaN(month)) return;
    const lastDay = new Date(year, month, 0).getDate();
    const dateFrom = `${yearStr}-${monthStr}-01`;
    const dateTo = `${yearStr}-${monthStr}-${String(lastDay).padStart(2, '0')}`;
    this.router.navigate(['/registro-auditoria'], {
      queryParams: { historico: 1, dateFrom, dateTo },
    });
  }

  drillDownToCategory(categoryKey: string): void {
    const from = this.facade.dateFrom() ?? this.toIsoDate(this.facade.oldestDate());
    const to = this.facade.dateTo() ?? this.toIsoDate(this.facade.newestDate());
    const queryParams: Record<string, string | number> = { historico: 1, category: categoryKey };
    if (from) queryParams['dateFrom'] = from;
    if (to) queryParams['dateTo'] = to;
    this.router.navigate(['/registro-auditoria'], { queryParams });
  }

  drillDownToUser(userUuid: string): void {
    const from = this.facade.dateFrom() ?? this.toIsoDate(this.facade.oldestDate());
    const to = this.facade.dateTo() ?? this.toIsoDate(this.facade.newestDate());
    const queryParams: Record<string, string | number> = { historico: 1, userId: userUuid };
    if (from) queryParams['dateFrom'] = from;
    if (to) queryParams['dateTo'] = to;
    this.router.navigate(['/registro-auditoria'], { queryParams });
  }

  drillDownToAnomalies(): void {
    const from = this.facade.dateFrom() ?? this.toIsoDate(this.facade.oldestDate());
    const to = this.facade.dateTo() ?? this.toIsoDate(this.facade.newestDate());
    const queryParams: Record<string, string | number> = { historico: 1, anomalyOnly: 1 };
    if (from) queryParams['dateFrom'] = from;
    if (to) queryParams['dateTo'] = to;
    this.router.navigate(['/registro-auditoria'], { queryParams });
  }

  private toIsoDate(d: Date | null): string | null {
    if (!d) return null;
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
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
