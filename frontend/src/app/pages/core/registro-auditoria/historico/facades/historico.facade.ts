import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { Subject, takeUntil } from 'rxjs';
import {
  ArchivedAuditStatsApi,
  AuditLogService,
  MonthlyArchivedCountApi,
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

@Injectable()
export class HistoricoFacade {
  private readonly service = inject(AuditLogService);
  private readonly destroy$ = new Subject<void>();

  // ── Private state ────────────────────────────────────────────
  private readonly _stats = signal<ArchivedAuditStatsApi | null>(null);
  private readonly _isLoading = signal<boolean>(false);
  private readonly _loadError = signal<string | null>(null);
  private readonly _lastUpdatedAt = signal<Date | null>(null);

  // ── Public readonly ──────────────────────────────────────────
  public readonly stats: Signal<ArchivedAuditStatsApi | null> = this._stats.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();
  public readonly loadError: Signal<string | null> = this._loadError.asReadonly();
  public readonly lastUpdatedAt: Signal<Date | null> = this._lastUpdatedAt.asReadonly();

  // ── Derived signals ──────────────────────────────────────────
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

  public readonly monthlyAverage = computed<number>(() => {
    const total = this.total();
    const months = this.monthlyPoints().length;
    if (months === 0) return 0;
    return Math.round(total / months);
  });

  // ── Setters ──────────────────────────────────────────────────
  public setStats(value: ArchivedAuditStatsApi | null): void { this._stats.set(value); }
  public setLoading(value: boolean): void { this._isLoading.set(value); }
  public setLoadError(value: string | null): void { this._loadError.set(value); }
  public setLastUpdatedAt(value: Date | null): void { this._lastUpdatedAt.set(value); }

  // ── Business ────────────────────────────────────────────────
  public loadStats(): void {
    this.setLoading(true);
    this.setLoadError(null);
    this.service
      .getArchivedStats()
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

  public ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}

function formatYearMonth(d: Date): string {
  return `${MONTH_LABELS_ES[d.getMonth()]} ${d.getFullYear()}`;
}

function formatMonthKey(key: string): string {
  // key = "YYYY-MM"
  const [y, m] = key.split('-');
  const mi = Math.max(0, Math.min(11, Number(m) - 1));
  return `${MONTH_LABELS_ES[mi]} ${y.slice(2)}`;
}
