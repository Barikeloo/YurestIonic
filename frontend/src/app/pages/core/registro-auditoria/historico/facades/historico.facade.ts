import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { Subject, takeUntil } from 'rxjs';
import {
  AnomalyKindCountApi,
  ArchivedAuditStatsApi,
  AuditLogService,
  BrokenAuditEventApi,
  CategoryArchivedCountApi,
  LatestVerifyResultApi,
  MonthlyArchivedCountApi,
  TopArchivedUserApi,
  VerifyAuditChainApi,
} from '../../../../../services/audit-log.service';

const MONTH_LABELS_ES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

export interface MonthlyPoint {
  key: string;
  label: string;
  count: number;
  ratio: number;
  displayHeight: number;
}

export interface PeakMonth {
  key: string;
  label: string;
  count: number;
}

export interface CategoryRow {
  key: string;
  label: string;
  count: number;
  ratio: number;
  displayWidth: number;
}

export interface TopUserRow {
  uuid: string;
  name: string;
  role: string | null;
  count: number;
  initials: string;
}

export interface AnomalyKindRow {
  kind: string;
  label: string;
  count: number;
}

const ANOMALY_LABELS_ES: Record<string, string> = {
  auth_failed_burst: 'Burst de PIN fallidos',
  caja_mismatch: 'Cierre de caja con descuadre',
};

const CATEGORY_LABELS_ES: Record<string, string> = {
  auth: 'Acceso',
  order: 'Pedidos',
  caja: 'Caja',
  sale: 'Ventas',
  table: 'Mesas',
  catalog: 'Catálogo',
  config: 'Config.',
  restaurant: 'Restaurante',
  system: 'Sistema',
};

function categoryLabel(raw: string): string {
  return CATEGORY_LABELS_ES[raw] ?? raw.charAt(0).toUpperCase() + raw.slice(1);
}

function userInitials(name: string): string {
  const parts = name.trim().split(/\s+/);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

export type RangePreset = 'all' | 'lastMonth' | 'lastQuarter' | 'lastYear' | 'custom';

export type VerifyState = 'idle' | 'loading' | 'success' | 'error';

export interface VerifyResult {
  isValid: boolean;
  totalEvents: number;
  verifiedCount: number;
  brokenEvents: BrokenAuditEventApi[];
  firstBrokenIndex: number | null;
  verifiedAt: Date;
}

export interface RangePresetOption {
  id: RangePreset;
  label: string;
  sub: string;
}

export const RANGE_PRESETS: ReadonlyArray<RangePresetOption> = [
  { id: 'all',         label: 'Todo el histórico', sub: 'Sin filtro temporal' },
  { id: 'lastYear',    label: 'Último año',         sub: '12 meses hacia atrás' },
  { id: 'lastQuarter', label: 'Último trimestre',   sub: '3 meses hacia atrás' },
  { id: 'lastMonth',   label: 'Último mes',         sub: '30 días hacia atrás' },
];

@Injectable()
export class HistoricoFacade {
  private readonly service = inject(AuditLogService);
  private readonly destroy$ = new Subject<void>();

  private readonly _stats = signal<ArchivedAuditStatsApi | null>(null);
  private readonly _isLoading = signal<boolean>(false);
  private readonly _loadError = signal<string | null>(null);
  private readonly _lastUpdatedAt = signal<Date | null>(null);
  private readonly _exportMenuOpen = signal<boolean>(false);
  private readonly _rangeMenuOpen = signal<boolean>(false);
  private readonly _activePreset = signal<RangePreset>('all');
  private readonly _dateFrom = signal<string | null>(null);
  private readonly _dateTo = signal<string | null>(null);
  private readonly _verifyState = signal<VerifyState>('idle');
  private readonly _verifyResult = signal<VerifyResult | null>(null);
  private readonly _verifyError = signal<string | null>(null);

  public readonly stats: Signal<ArchivedAuditStatsApi | null> = this._stats.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();
  public readonly loadError: Signal<string | null> = this._loadError.asReadonly();
  public readonly lastUpdatedAt: Signal<Date | null> = this._lastUpdatedAt.asReadonly();
  public readonly exportMenuOpen: Signal<boolean> = this._exportMenuOpen.asReadonly();
  public readonly rangeMenuOpen: Signal<boolean> = this._rangeMenuOpen.asReadonly();
  public readonly activePreset: Signal<RangePreset> = this._activePreset.asReadonly();
  public readonly dateFrom: Signal<string | null> = this._dateFrom.asReadonly();
  public readonly dateTo: Signal<string | null> = this._dateTo.asReadonly();
  public readonly verifyState: Signal<VerifyState> = this._verifyState.asReadonly();
  public readonly verifyResult: Signal<VerifyResult | null> = this._verifyResult.asReadonly();
  public readonly verifyError: Signal<string | null> = this._verifyError.asReadonly();

  public readonly hasData = computed(() => (this._stats()?.total ?? 0) > 0);

  public readonly total = computed(() => this._stats()?.total ?? 0);

  public readonly oldestDate = computed<Date | null>(() => {
    const iso = this._stats()?.oldest_created_at ?? null;
    return iso ? new Date(iso) : null;
  });

  public readonly newestDate = computed<Date | null>(() => {
    const iso = this._stats()?.newest_created_at ?? null;
    return iso ? new Date(iso) : null;
  });

  public readonly rangeLabel = computed<string>(() => {
    const o = this.oldestDate(), n = this.newestDate();
    if (!o || !n) return '—';
    return `${formatYearMonth(o)} – ${formatYearMonth(n)}`;
  });

  public readonly spanInMonths = computed<number>(() => {
    const o = this.oldestDate(), n = this.newestDate();
    if (!o || !n) return 0;
    return Math.max(1, (n.getFullYear() - o.getFullYear()) * 12 + (n.getMonth() - o.getMonth()) + 1);
  });

  public readonly monthlyPoints = computed<MonthlyPoint[]>(() => {
    const raw = this._stats()?.monthly_breakdown ?? [];
    if (raw.length === 0) return [];
    const max = Math.max(...raw.map((m) => m.count));
    return raw.map((m: MonthlyArchivedCountApi) => {
      const ratio = max > 0 ? m.count / max : 0;
      return {
        key: m.month,
        label: formatMonthKey(m.month),
        count: m.count,
        ratio,
        displayHeight: Math.max(4, Math.round(ratio * 100)),
      };
    });
  });

  public readonly peakMonth = computed<PeakMonth | null>(() => {
    const points = this.monthlyPoints();
    if (points.length === 0) return null;
    return points.reduce<PeakMonth>(
      (top, p) => (p.count > top.count ? { key: p.key, label: p.label, count: p.count } : top),
      { key: points[0].key, label: points[0].label, count: -1 },
    );
  });

  public readonly categoriesBreakdown = computed<CategoryRow[]>(() => {
    const raw = this._stats()?.by_category ?? [];
    if (raw.length === 0) return [];
    const max = Math.max(...raw.map((c) => c.count));
    return raw.map((c: CategoryArchivedCountApi) => {
      const ratio = max > 0 ? c.count / max : 0;
      return {
        key: c.category,
        label: categoryLabel(c.category),
        count: c.count,
        ratio,
        displayWidth: Math.max(6, Math.round(ratio * 100)),
      };
    });
  });

  public readonly topUsers = computed<TopUserRow[]>(() => {
    const raw = this._stats()?.top_users ?? [];
    return raw.map((u: TopArchivedUserApi) => ({
      uuid: u.uuid,
      name: u.name,
      role: u.role,
      count: u.count,
      initials: userInitials(u.name),
    }));
  });

  public readonly anomalies = computed<AnomalyKindRow[]>(() => {
    const raw = this._stats()?.by_anomaly_kind ?? [];
    return raw.map((a: AnomalyKindCountApi) => ({
      kind: a.kind,
      label: ANOMALY_LABELS_ES[a.kind] ?? a.kind,
      count: a.count,
    }));
  });

  public readonly totalAnomalies = computed<number>(() => {
    return this.anomalies().reduce((sum, a) => sum + a.count, 0);
  });

  public readonly monthlyAverage = computed<number>(() => {
    const total = this.total();
    const months = this.monthlyPoints().length;
    if (months === 0) return 0;
    return Math.round(total / months);
  });


  public readonly csvExportUrl = computed<string>(() => this.service.buildExportUrl('csv', {
    includeArchived: true,
    dateFrom: this._dateFrom() ?? undefined,
    dateTo: this._dateTo() ?? undefined,
  }));

  public readonly ndjsonExportUrl = computed<string>(() => this.service.buildExportUrl('ndjson', {
    includeArchived: true,
    dateFrom: this._dateFrom() ?? undefined,
    dateTo: this._dateTo() ?? undefined,
  }));

  public readonly hasActiveRange = computed<boolean>(() => this._activePreset() !== 'all');

  public readonly activeRangeLabel = computed<string | null>(() => {
    const preset = this._activePreset();
    if (preset === 'all') return null;
    const knownPreset = RANGE_PRESETS.find((p) => p.id === preset);
    if (knownPreset && preset !== 'custom') return knownPreset.label;
    const from = this._dateFrom();
    const to = this._dateTo();
    if (from && to) return `${from} → ${to}`;
    if (from) return `desde ${from}`;
    if (to) return `hasta ${to}`;
    return 'Personalizado';
  });

  public setStats(value: ArchivedAuditStatsApi | null): void { this._stats.set(value); }
  public setLoading(value: boolean): void { this._isLoading.set(value); }
  public setLoadError(value: string | null): void { this._loadError.set(value); }
  public setLastUpdatedAt(value: Date | null): void { this._lastUpdatedAt.set(value); }
  public setExportMenuOpen(value: boolean): void { this._exportMenuOpen.set(value); }
  public toggleExportMenu(): void { this._exportMenuOpen.update((v) => !v); }
  public closeExportMenu(): void { this._exportMenuOpen.set(false); }

  public toggleRangeMenu(): void { this._rangeMenuOpen.update((v) => !v); }
  public closeRangeMenu(): void { this._rangeMenuOpen.set(false); }

  public applyPreset(preset: RangePreset): void {
    if (preset === this._activePreset()) {
      this.closeRangeMenu();
      return;
    }

    if (preset === 'all') {
      this._activePreset.set('all');
      this._dateFrom.set(null);
      this._dateTo.set(null);
    } else if (preset !== 'custom') {
      const from = this.computePresetFrom(preset);
      this._activePreset.set(preset);
      this._dateFrom.set(from);
      this._dateTo.set(null);
    }

    this.closeRangeMenu();
    this.loadStats();
  }

  public applyCustomRange(from: string | null, to: string | null): void {
    const cleanFrom = from && from.length > 0 ? from : null;
    const cleanTo = to && to.length > 0 ? to : null;
    if (cleanFrom === null && cleanTo === null) {
      this.applyPreset('all');
      return;
    }
    this._activePreset.set('custom');
    this._dateFrom.set(cleanFrom);
    this._dateTo.set(cleanTo);
    this.closeRangeMenu();
    this.loadStats();
  }

  public clearRange(): void { this.applyPreset('all'); }

  private computePresetFrom(preset: Exclude<RangePreset, 'all' | 'custom'>): string {
    const now = new Date();
    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    if (preset === 'lastMonth') d.setDate(d.getDate() - 30);
    else if (preset === 'lastQuarter') d.setMonth(d.getMonth() - 3);
    else if (preset === 'lastYear') d.setFullYear(d.getFullYear() - 1);
    return d.toISOString().slice(0, 10);
  }

  public loadStats(): void {
    this.setLoading(true);
    this.setLoadError(null);
    this.service
      .getArchivedStats({
        dateFrom: this._dateFrom() ?? undefined,
        dateTo: this._dateTo() ?? undefined,
      })
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          this.setStats(response);
          this.setLastUpdatedAt(new Date());
          this.setLoading(false);
        },
        error: (err: { message?: string }) => {
          this.setLoadError(err?.message ?? 'No se pudo cargar el histórico.');
          this.setLoading(false);
        },
      });
  }

  public loadLatestVerify(): void {
    this.service
      .getLatestVerifyResult()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response) => {
          if (response.latest === null) return;
          this._verifyResult.set({
            isValid: response.latest.is_valid,
            totalEvents: response.latest.total_events,
            verifiedCount: response.latest.verified_count,
            brokenEvents: response.latest.broken_events,
            firstBrokenIndex: response.latest.first_broken_index,
            verifiedAt: new Date(response.latest.verified_at),
          });
          this._verifyState.set('success');
        },
      });
  }

  public runVerify(): void {
    this._verifyState.set('loading');
    this._verifyError.set(null);
    this.service
      .verifyChain()
      .pipe(takeUntil(this.destroy$))
      .subscribe({
        next: (response: VerifyAuditChainApi) => {
          const result: VerifyResult = {
            isValid: response.is_valid,
            totalEvents: response.total_events,
            verifiedCount: response.verified_count,
            brokenEvents: response.broken_events,
            firstBrokenIndex: response.first_broken_index,
            verifiedAt: new Date(),
          };
          this._verifyResult.set(result);
          this._verifyState.set('success');
        },
        error: (err: { message?: string }) => {
          this._verifyError.set(err?.message ?? 'No se pudo verificar la cadena.');
          this._verifyState.set('error');
        },
      });
  }

  public ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}

function formatYearMonth(d: Date): string {
  return `${MONTH_LABELS_ES[d.getMonth()]} ${d.getFullYear()}`;
}

function formatMonthKey(key: string): string {

  const [y, m] = key.split('-');
  const mi = Math.max(0, Math.min(11, Number(m) - 1));
  return `${MONTH_LABELS_ES[mi]} ${y.slice(2)}`;
}
