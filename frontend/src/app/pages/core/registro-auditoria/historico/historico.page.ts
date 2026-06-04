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

  ngOnInit(): void {
    this.facade.loadStats();
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
