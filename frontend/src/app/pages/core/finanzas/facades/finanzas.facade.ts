import { computed, DestroyRef, inject, Injectable, signal } from '@angular/core';
import { takeUntilDestroyed, toObservable, toSignal } from '@angular/core/rxjs-interop';
import { catchError, combineLatest, EMPTY, forkJoin, switchMap } from 'rxjs';
import { AuditAlertApi, AuditAlertService } from '../../../../services/audit-alert.service';
import { AppContextService } from '../../../../core/services/app-context.service';
import { FinanzasService } from '../../../../services/finanzas.service';
import {
  TpvService,
  TpvCashSessionSummary,
  TpvCashSessionListItem,
  TpvOrder,
  TpvTableItem,
  TpvZoneItem,
} from '../../../../features/cash/services/tpv.service';
import { OrderStatus } from '../../../../core/enums/order-status.enum';
import {
  MOCK_BY_DAY,
  MOCK_BY_FAMILY,
  MOCK_BY_HOUR,
  MOCK_BY_HOUR_PREV,
  MOCK_BY_HOUR_LAST_WEEK,
  MOCK_BY_METHOD,
  MOCK_CANNIBALS,
  MOCK_CASH_HISTORY,
  MOCK_CASH_SESSION,
  MOCK_CANCELLATIONS,
  MOCK_CROSS_SELL,
  MOCK_DEAD_STOCK,
  MOCK_EMPLOYEES,
  MOCK_FORECAST,
  MOCK_HEATMAP,
  MOCK_META,
  MOCK_OPEN_TABLES,
  MOCK_ORDERS,
  MOCK_PENDING_PAYMENTS,
  MOCK_PRE_CLOSE,
  MOCK_PRODUCT_RANKING,
  MOCK_PRODUCT_TRENDS,
  MOCK_QUARTERLY,
  MOCK_TAX_BREAKDOWN,
  MOCK_SPARKS,
  MOCK_SUMMARY,
  MOCK_TICKET_DETAIL,
  MOCK_TOP_PRODUCTS,
  MOCK_ZONES_LAYOUT,
} from '../mock/finanzas.mock';
import type {
  CashMovementItem,
  CashSessionHistory,
  DashboardSummaryResponse,
  EmployeesReportResponse,
  FamilyData,
  FinanzasAlert,
  FinanzasPeriod,
  FinanzasTab,
  HeatmapRow,
  OpenTable,
  PendingPayment,
  ProductsReportResponse,
  Quarter,
  ResumenVariant,
  SaleDetailResponse,
  SalesReportResponse,
  TaxReportResponse,
  TicketDetail,
  TopProduct,
} from '../models/finanzas.models';

interface CashSessionViewData {
  id: string;
  operator: string;
  opened: string;
  initial: number;
  cashPayments: number;
  cashIn: number;
  cashOut: number;
  movements: CashMovementItem[];
}

interface Insight {
  icon: string;
  text: string;
  color: string;
}

const FAMILY_PALETTE = ['#ff4d4d','#1a9e5a','#0077cc','#d18a1c','#7857d6','#3d3d3d','#ff8800','#9b59b6'];

function currentQuarter(): Quarter {
  const m = new Date().getMonth();
  if (m < 3) return 'T1';
  if (m < 6) return 'T2';
  if (m < 9) return 'T3';
  return 'T4';
}

@Injectable()
export class FinanzasFacade {

  private readonly auditAlertService = inject(AuditAlertService);
  private readonly appContext        = inject(AppContextService);
  private readonly tpvService        = inject(TpvService);
  private readonly finanzasService   = inject(FinanzasService);
  private readonly destroyRef        = inject(DestroyRef);

  private readonly _restaurantCtx = toSignal(this.appContext.activeRestaurant$, { initialValue: null });

  // ── UI state ──────────────────────────────────────────────────────────────
  private readonly _activeTab      = signal<FinanzasTab>('resumen');
  private readonly _period         = signal<FinanzasPeriod>('today');
  private readonly _resumenVariant = signal<ResumenVariant>('A');
  private readonly _showCompare    = signal(true);

  // ── Alerts state ─────────────────────────────────────────────────────────
  private readonly _alerts        = signal<FinanzasAlert[]>([]);
  private readonly _unreadCount   = signal(0);
  private readonly _loadingAlerts = signal(false);

  // ── Summary state ────────────────────────────────────────────────────────
  private readonly _summaryApi      = signal<DashboardSummaryResponse | null>(null);
  private readonly _loadingSummary  = signal(true);
  private readonly _periodChanges$  = toObservable(this._period);

  // ── Heatmap state ────────────────────────────────────────────────────────
  private readonly _heatmapApi = signal<HeatmapRow[] | null>(null);

  // ── Sales report state ──────────────────────────────────────────────────
  private readonly _salesReport   = signal<SalesReportResponse | null>(null);
  private readonly _loadingSales  = signal(true);

  // ── Sale detail state ───────────────────────────────────────────────────
  private readonly _saleDetailApi = signal<SaleDetailResponse | null>(null);
  private readonly _loadingDetail = signal(false);

  // ── Products report state ────────────────────────────────────────────────
  private readonly _productsReport   = signal<ProductsReportResponse | null>(null);
  private readonly _loadingProducts  = signal(false);

  // ── Employees report state ───────────────────────────────────────────────
  private readonly _employeesReport  = signal<EmployeesReportResponse | null>(null);
  private readonly _loadingEmployees = signal(false);

  // ── Tax report state ─────────────────────────────────────────────────────
  private readonly _taxReport    = signal<TaxReportResponse | null>(null);
  private readonly _loadingTaxes = signal(false);
  private readonly _activeQ      = signal<Quarter>(currentQuarter());
  private readonly _activeQ$     = toObservable(this._activeQ);

  // ── Open orders state ────────────────────────────────────────────────────
  private readonly _openOrders       = signal<TpvOrder[]>([]);
  private readonly _openOrdersLoaded = signal(false);

  // ── Table / Zone state ────────────────────────────────────────────────────
  private readonly _tables = signal<TpvTableItem[]>([]);
  private readonly _zones  = signal<TpvZoneItem[]>([]);

  // ── Cash state ────────────────────────────────────────────────────────────
  private readonly _activeSessionItem = signal<TpvCashSessionListItem | null>(null);
  private readonly _cashSummary       = signal<TpvCashSessionSummary | null>(null);
  private readonly _cashMovementsList = signal<CashMovementItem[]>([]);
  private readonly _cashHistoryList   = signal<TpvCashSessionListItem[]>([]);
  private readonly _loadingCash       = signal(false);

  // ── Public readonly API ───────────────────────────────────────────────────
  public readonly activeTab      = this._activeTab.asReadonly();
  public readonly period         = this._period.asReadonly();
  public readonly resumenVariant = this._resumenVariant.asReadonly();
  public readonly showCompare    = this._showCompare.asReadonly();
  public readonly alerts         = this._alerts.asReadonly();
  public readonly loadingAlerts  = this._loadingAlerts.asReadonly();
  public readonly cashSummary     = this._cashSummary.asReadonly();
  public readonly loadingCash     = this._loadingCash.asReadonly();
  public readonly loadingSummary  = this._loadingSummary.asReadonly();
  public readonly salesReport     = this._salesReport.asReadonly();
  public readonly loadingSales    = this._loadingSales.asReadonly();
  public readonly saleDetailApi    = this._saleDetailApi.asReadonly();
  public readonly loadingDetail    = this._loadingDetail.asReadonly();
  public readonly productsReport   = this._productsReport.asReadonly();
  public readonly loadingProducts  = this._loadingProducts.asReadonly();
  public readonly employeesReport  = this._employeesReport.asReadonly();
  public readonly loadingEmployees = this._loadingEmployees.asReadonly();
  public readonly taxReport        = this._taxReport.asReadonly();
  public readonly loadingTaxes     = this._loadingTaxes.asReadonly();
  public readonly activeQ          = this._activeQ.asReadonly();

  public readonly restaurantName = computed(() => this._restaurantCtx()?.name ?? '');

  public readonly periodLabel = computed(() => {
    const map: Record<FinanzasPeriod, string> = {
      today:     'Hoy',
      yesterday: 'Ayer',
      week:      'Esta semana',
      month:     'Este mes',
    };
    return map[this._period()];
  });

  public readonly unreadAlerts = this._unreadCount.asReadonly();

  // ── Cash computed (reemplaza mock cuando hay datos reales) ────────────────
  public readonly cashSession = computed((): CashSessionViewData => {
    const session = this._activeSessionItem();
    const summary = this._cashSummary();
    if (!session || !summary) return MOCK_CASH_SESSION;

    return {
      id:           session.uuid.slice(-6).toUpperCase(),
      operator:     '—',
      opened:       this.formatTime(session.opened_at),
      initial:      summary.initial_amount_cents,
      cashPayments: summary.total_cash_payments,
      cashIn:       summary.total_in_movements,
      cashOut:      summary.total_out_movements,
      movements:    this._cashMovementsList(),
    };
  });

  public readonly cashHistory = computed((): CashSessionHistory[] => {
    const sessions = this._cashHistoryList().filter(s => s.status === 'closed');
    if (!sessions.length) return MOCK_CASH_HISTORY;
    return sessions.map(s => ({
      id:          s.uuid.slice(-6),
      opened:      this.formatDateTime(s.opened_at),
      closed:      s.closed_at ? this.formatDateTime(s.closed_at) : '—',
      operator:    '—',
      sales:       s.net,
      theoretical: s.expected_amount_cents ?? 0,
      counted:     s.final_amount_cents ?? 0,
      diff:        s.discrepancy_cents ?? 0,
      tickets:     s.tickets,
    }));
  });

  public readonly cashTheoretical = computed(() => {
    const summary = this._cashSummary();
    if (summary) return summary.expected_amount;
    return MOCK_CASH_SESSION.initial + MOCK_BY_METHOD.cash.v + MOCK_CASH_SESSION.cashIn - MOCK_CASH_SESSION.cashOut;
  });

  // ── Setters ───────────────────────────────────────────────────────────────
  public setTab(tab: FinanzasTab): void             { this._activeTab.set(tab); }
  public setPeriod(p: FinanzasPeriod): void         { this._period.set(p); }
  public setResumenVariant(v: ResumenVariant): void { this._resumenVariant.set(v); }
  public setShowCompare(v: boolean): void           { this._showCompare.set(v); }
  public setActiveQ(q: Quarter): void               { this._activeQ.set(q); }

  // ── Init ──────────────────────────────────────────────────────────────────
  public init(): void {
    this.loadAlerts();
    this.loadCashData();
    this.loadOpenOrders();
    this._periodChanges$
      .pipe(
        switchMap(period => {
          this._loadingSummary.set(true);
          return this.finanzasService.getSummary(period).pipe(
            catchError(() => { this._loadingSummary.set(false); return EMPTY; }),
          );
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(res => {
        this._summaryApi.set(res);
        this._loadingSummary.set(false);
      });

    this._periodChanges$
      .pipe(
        switchMap(period => {
          this._loadingSales.set(true);
          return this.finanzasService.getSales(period).pipe(
            catchError(() => { this._loadingSales.set(false); return EMPTY; }),
          );
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(res => {
        this._salesReport.set(res);
        this._loadingSales.set(false);
      });

    this._periodChanges$
      .pipe(
        switchMap(period => {
          this._loadingProducts.set(true);
          return this.finanzasService.getProducts(period).pipe(
            catchError(() => { this._loadingProducts.set(false); return EMPTY; }),
          );
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(res => {
        this._productsReport.set(res);
        this._loadingProducts.set(false);
      });

    this._periodChanges$
      .pipe(
        switchMap(period => {
          this._loadingEmployees.set(true);
          return this.finanzasService.getEmployees(period).pipe(
            catchError(() => { this._loadingEmployees.set(false); return EMPTY; }),
          );
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(res => {
        this._employeesReport.set(res);
        this._loadingEmployees.set(false);
      });

    combineLatest([this._periodChanges$, this._activeQ$])
      .pipe(
        switchMap(([period, quarter]) => {
          this._loadingTaxes.set(true);
          return this.finanzasService.getTaxes(period, quarter).pipe(
            catchError(() => { this._loadingTaxes.set(false); return EMPTY; }),
          );
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(res => {
        this._taxReport.set(res);
        this._loadingTaxes.set(false);
      });

    this.finanzasService.getHeatmap()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(res => this._heatmapApi.set(res.data));
  }

  // ── Alerts ────────────────────────────────────────────────────────────────
  public loadAlerts(): void {
    this._loadingAlerts.set(true);
    this.auditAlertService.listAlerts()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res) => {
          this._alerts.set(res.data.map(a => this.mapApiAlert(a)));
          this._unreadCount.set(res.unread_count);
          this._loadingAlerts.set(false);
        },
        error: () => this._loadingAlerts.set(false),
      });
  }

  public markAlertsRead(): void {
    this.auditAlertService.markAllAsRead()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.loadAlerts());
  }

  // ── Cash ──────────────────────────────────────────────────────────────────
  public loadCashData(): void {
    this._loadingCash.set(true);

    this.tpvService.listCashSessions()
      .pipe(
        switchMap(res => {
          const sessions = res.sessions;
          this._cashHistoryList.set(sessions);

          const active = sessions.find(s => s.status === 'open' || s.status === 'closing');
          if (!active) {
            this._loadingCash.set(false);
            return EMPTY;
          }

          this._activeSessionItem.set(active);
          return forkJoin({
            summary:   this.tpvService.getCashSessionSummary(active.uuid),
            movements: this.tpvService.listCashMovements(active.uuid),
          });
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe({
        next: ({ summary, movements }) => {
          this._cashSummary.set(summary);
          this._cashMovementsList.set(
            movements.movements.map(m => ({
              id:     m.uuid,
              type:   m.type,
              reason: m.description ?? m.reason_code,
              amount: m.amount_cents,
              time:   this.formatTime(m.created_at),
              user:   '—',
            }))
          );
          this._loadingCash.set(false);
        },
        error: () => this._loadingCash.set(false),
      });
  }

  // ── Sale detail ──────────────────────────────────────────────────────────
  public loadSaleDetail(uuid: string): void {
    this._loadingDetail.set(true);
    this.finanzasService.getSaleDetail(uuid)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res) => {
          this._saleDetailApi.set(res);
          this._loadingDetail.set(false);
        },
        error: () => this._loadingDetail.set(false),
      });
  }

  // ── Products report ──────────────────────────────────────────────────────
  public loadProducts(): void {
    this._loadingProducts.set(true);
    this.finanzasService.getProducts(this._period())
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res) => {
          this._productsReport.set(res);
          this._loadingProducts.set(false);
        },
        error: () => this._loadingProducts.set(false),
      });
  }

  // ── Open orders ───────────────────────────────────────────────────────────
  public loadOpenOrders(): void {
    this.tpvService.listOrders()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: orders => {
          this._openOrders.set(orders);
          this._openOrdersLoaded.set(true);
        },
        error: () => this._openOrdersLoaded.set(true),
      });

    this.tpvService.listTables()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(tables => this._tables.set(tables));

    this.tpvService.listZones()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(zones => this._zones.set(zones));
  }

  private readonly _tableLookup = computed(() => {
    const tables = this._tables();
    const zones  = this._zones();
    const zoneMap = new Map(zones.map(z => [z.id, z.name]));
    const map = new Map<string, { name: string; zone: string }>();
    for (const t of tables) {
      map.set(t.id, { name: t.name, zone: zoneMap.get(t.zone_id) ?? '—' });
    }
    return map;
  });

  public get openTables(): OpenTable[] {
    if (!this._openOrdersLoaded()) return MOCK_OPEN_TABLES;
    const tableLookup = this._tableLookup();
    return this._openOrders()
      .filter(o => o.status === OrderStatus.OPEN || o.status === OrderStatus.TO_CHARGE)
      .map(o => {
        const mins = Math.floor((Date.now() - new Date(o.opened_at).getTime()) / 60000);
        const tbl = tableLookup.get(o.table_id);
        return {
          id:          o.id,
          zone:        tbl ? `${tbl.zone} · ${tbl.name}` : '—',
          diners:      o.diners,
          opened:      this.formatTime(o.opened_at),
          minutesOpen: mins,
          current:     o.total,
          waiter:      '—',
          state:       o.status === OrderStatus.TO_CHARGE ? 'paying' : 'eating',
          lastEvent:   this.formatTime(o.opened_at),
          alert:       mins > 120 ? 'long' : undefined,
        };
      });
  }

  // ── Private helpers ───────────────────────────────────────────────────────
  private mapApiAlert(a: AuditAlertApi): FinanzasAlert {
    return {
      id:    a.uuid,
      type:  this.mapAlertType(a.anomaly_kind),
      title: a.action,
      sub:   a.summary ?? '',
      time:  this.formatAlertTime(a.created_at),
      tab:   this.mapAlertTab(a.entity_type),
    };
  }

  private mapAlertType(kind: string): FinanzasAlert['type'] {
    const k = kind.toLowerCase();
    if (k.includes('critical') || k.includes('unauthorized') || k.includes('fraud')) return 'critical';
    if (k.includes('warning') || k.includes('suspicious') || k.includes('discount')) return 'warning';
    return 'info';
  }

  private mapAlertTab(entityType: string): FinanzasTab {
    const e = entityType.toLowerCase();
    if (e.includes('sale') || e.includes('order') || e.includes('ticket')) return 'ventas';
    if (e.includes('product')) return 'productos';
    if (e.includes('user') || e.includes('employee')) return 'empleados';
    if (e.includes('cash') || e.includes('session')) return 'caja';
    return 'resumen';
  }

  private formatAlertTime(createdAt: string): string {
    const mins = Math.floor((Date.now() - new Date(createdAt).getTime()) / 60000);
    if (mins < 60)   return `hace ${mins}m`;
    if (mins < 1440) return `hace ${Math.floor(mins / 60)}h`;
    return `hace ${Math.floor(mins / 1440)}d`;
  }

  private formatTime(isoString: string): string {
    return new Date(isoString).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false });
  }

  private formatDateTime(isoString: string): string {
    const d = new Date(isoString);
    const day   = d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' });
    const time  = d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false });
    return `${day} ${time}`;
  }

  // ── Mock data (reemplazados fase a fase) ──────────────────────────────────
  public readonly meta            = MOCK_META;
  public readonly byHourLastWeek  = MOCK_BY_HOUR_LAST_WEEK;
  public readonly heatmap = computed((): HeatmapRow[] => {
    const api = this._heatmapApi();
    return api ?? MOCK_HEATMAP;
  });
  public readonly deadStock       = MOCK_DEAD_STOCK;
  public readonly orders          = MOCK_ORDERS;
  public readonly employees       = MOCK_EMPLOYEES;
  public readonly crossSell       = MOCK_CROSS_SELL;
  public readonly productTrends   = MOCK_PRODUCT_TRENDS;
  public readonly pendingPayments = computed((): PendingPayment[] => {
    const orders = this._openOrders();
    const loaded = this._openOrdersLoaded();
    if (!loaded) return MOCK_PENDING_PAYMENTS;
    const toCharge = orders.filter(o => o.status === OrderStatus.TO_CHARGE);
    if (!toCharge.length) return [];
    const tableLookup = this._tableLookup();
    return toCharge.map(o => {
      const tbl = tableLookup.get(o.table_id);
      return {
        id:    o.id,
        zone:  tbl ? `${tbl.zone} · ${tbl.name}` : '—',
        total: o.remaining_total ?? o.total,
        issue: 'cuenta_pedida',
        since: this.formatTime(o.opened_at),
      };
    });
  });
  public readonly cancellations   = MOCK_CANCELLATIONS;
  public readonly preClose        = MOCK_PRE_CLOSE;
  public readonly forecast        = MOCK_FORECAST;
  public readonly quarterly       = MOCK_QUARTERLY;
  public readonly taxBreakdown    = MOCK_TAX_BREAKDOWN;
  public readonly ticketDetail = computed((): TicketDetail => {
    const api = this._saleDetailApi();
    if (!api) return MOCK_TICKET_DETAIL;
    return {
      id:       api.uuid.slice(-8).toUpperCase(),
      zone:     api.table_name !== '—' ? api.table_name : api.zone_name,
      diners:   api.diners,
      opened:   this.formatTime(api.value_date),
      closed:   '',
      duration: api.duration_minutes !== null ? `${api.duration_minutes} min` : '—',
      waiter:   api.opened_by,
      status:   api.status === 'closed' ? 'paid' : api.status,
      lines:    api.lines.map(l => ({
        name:      l.product_name,
        qty:       l.qty,
        unitPrice: l.unit_price,
        tax:       l.tax_pct,
        total:     l.total,
      })),
      payments:  api.payments.map(p => ({
        method: p.method,
        amount: p.amount,
        tip:    p.tip ?? 0,
      })),
      taxBreakdown: api.tax_breakdown.map(tb => ({
        rate: tb.rate,
        base: tb.base,
        tax:  tb.tax,
      })),
      note:      api.cancel_reason ?? '',
      subtotal:  api.subtotal,
      taxTotal:  api.tax_total,
      tipsTotal: api.tips_total,
    };
  });
  public readonly productRanking  = MOCK_PRODUCT_RANKING;
  public readonly zonesLayout     = MOCK_ZONES_LAYOUT;
  public readonly cannibals       = MOCK_CANNIBALS;

  // ── Summary getters (real data when loaded, mock fallback) ────────────────
  public get summary() {
    const api = this._summaryApi();
    if (!api) return MOCK_SUMMARY;
    const k = api.kpis;
    return {
      revenue:   { v: k.revenue.v,    prev: k.revenue.prev,    deltaPct: k.revenue.delta_pct    },
      tickets:   { v: k.tickets.v,    prev: k.tickets.prev,    deltaPct: k.tickets.delta_pct    },
      avgTicket: { v: k.avg_ticket.v, prev: k.avg_ticket.prev, deltaPct: k.avg_ticket.delta_pct },
      itemsSold: { v: k.items_sold.v, prev: k.items_sold.prev, deltaPct: k.items_sold.delta_pct },
      diners:    { v: k.diners.v,     prev: k.diners.prev,     deltaPct: k.diners.delta_pct     },
      tipsCard:  { v: 0, prev: 0, deltaPct: 0 },
      cashOpen:  { v: 0, status: 'open' },
    };
  }

  public get sparks() {
    const api = this._summaryApi();
    if (!api) return MOCK_SPARKS;
    return {
      revenue:   api.sparks.revenue,
      tickets:   api.sparks.tickets,
      avgTicket: api.sparks.avg_ticket,
      items:     api.sparks.items,
    };
  }

  public get byHour()     { return this._summaryApi()?.by_hour      ?? MOCK_BY_HOUR;      }
  public get byHourPrev() { return this._summaryApi()?.by_hour_prev  ?? MOCK_BY_HOUR_PREV; }
  public get byDay()      { return this._summaryApi()?.by_day        ?? MOCK_BY_DAY;       }

  public get byFamily(): FamilyData[] {
    const api = this._summaryApi();
    if (!api) return MOCK_BY_FAMILY;
    return api.by_family.map((f, i) => ({
      label: f.label,
      v:     f.v,
      color: FAMILY_PALETTE[i % FAMILY_PALETTE.length],
    }));
  }

  public readonly topProducts = computed((): TopProduct[] => {
    const api = this._summaryApi();
    if (!api) return MOCK_TOP_PRODUCTS;
    const items = api.top_products;
    if (!items.length) return MOCK_TOP_PRODUCTS;
    return items.map((p, i) => ({
      name:     p.name,
      family:   p.family,
      units:    p.units,
      revenue:  p.revenue,
      cost:     0,
      avgDaily: 0,
      stock:    0,
      color:    FAMILY_PALETTE[i % FAMILY_PALETTE.length],
    }));
  });

  public get byMethod() {
    const api = this._summaryApi();
    if (!api) return MOCK_BY_METHOD;
    const m = api.by_payment_method;
    return {
      cash:       { v: m.cash,       n: 0 },
      card:       { v: m.card,       n: 0 },
      bizum:      { v: m.bizum,      n: 0 },
      voucher:    { v: m.voucher,    n: 0 },
      invitation: { v: m.invitation, n: 0 },
    };
  }

  // ── Computed helpers ──────────────────────────────────────────────────────
  public readonly totalRevenue = computed(() => this.summary.revenue.v);

  // ── Format helpers ────────────────────────────────────────────────────────
  public fmt(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 });
  }

  public fmtNum(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public fmtInt(n: number): string {
    return n.toLocaleString('es-ES');
  }

  public fmtPct(n: number, decimals = 1): string {
    return `${n >= 0 ? '+' : ''}${n.toFixed(decimals)}%`;
  }

  public sparklinePath(data: number[], W = 100, H = 24, pad = 2): string {
    if (!data.length) return '';
    const max = Math.max(...data, 1);
    const min = Math.min(...data, 0);
    const range = max - min || 1;
    return data
      .map((v, i) => {
        const x = pad + (i / (data.length - 1)) * (W - 2 * pad);
        const y = pad + (1 - (v - min) / range) * (H - 2 * pad);
        return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
      })
      .join(' ');
  }

  public sparklineArea(data: number[], W = 100, H = 24, pad = 2): string {
    const line = this.sparklinePath(data, W, H, pad);
    if (!line) return '';
    const lastX = (W - pad).toFixed(1);
    const firstX = pad.toFixed(1);
    return `${line} L ${lastX} ${H} L ${firstX} ${H} Z`;
  }

  public barPct(v: number, max: number): number {
    return max > 0 ? Math.max((v / max) * 95, v > 0 ? 2 : 0) : 0;
  }

  public heatBg(v: number, max: number, color = '#ff4d4d'): string {
    const t = v / (max || 1);
    if (t < 0.04) return 'rgba(250,250,250,1)';
    const opacity = Math.round((0.08 + t * 0.92) * 255).toString(16).padStart(2, '0');
    return `${color}${opacity}`;
  }

  public heatText(v: number, max: number): string {
    return v / (max || 1) > 0.55 ? '#fff' : '#0d0d0d';
  }

  public donutSegments(data: { label: string; v: number; color: string }[], size: number, thickness: number): Array<{
    color: string; label: string; v: number; frac: number; dashArray: string; dashOffset: string;
  }> {
    const r = (size - thickness) / 2;
    const circ = 2 * Math.PI * r;
    const total = data.reduce((s, d) => s + d.v, 0) || 1;
    const gap = 1.5;
    let offset = 0;
    return data.map(d => {
      const frac = d.v / total;
      const len = Math.max(circ * frac - gap, 1);
      const seg = {
        color: d.color, label: d.label, v: d.v, frac,
        dashArray: `${len} ${circ}`,
        dashOffset: `${-offset}`,
      };
      offset += circ * frac;
      return seg;
    });
  }

  public maxVal(data: { v: number }[]): number {
    return Math.max(...data.map(d => d.v), 1);
  }

  public tabLabel(tab: FinanzasTab): string {
    const labels: Record<FinanzasTab, string> = {
      resumen: 'Resumen', ventas: 'Ventas', productos: 'Productos',
      empleados: 'Empleados', caja: 'Caja', impuestos: 'Impuestos', informes: 'Informes',
    };
    return labels[tab];
  }

  readonly insights = computed((): Insight[] => {
    const api = this._summaryApi();
    if (!api) return [
      { icon: '★', text: 'Pico de comida a las 14h con 394 € · refuerza turno', color: '#ff4d4d' },
      { icon: '↑', text: 'Bebidas tira el carro: 36% del total (vs 32% ayer)',  color: '#1a9e5a' },
      { icon: '⚠', text: '4 productos sin ventas en 7 días · revisar carta',    color: '#d18a1c' },
    ];

    const result: Insight[] = [];
    const { kpis, by_hour, by_family, top_products, by_payment_method } = api;

    // Peak hour
    if (by_hour.length) {
      const peak = by_hour.reduce((a, b) => a.v >= b.v ? a : b);
      result.push({
        icon: '★',
        text: `Pico a las ${peak.l}h con ${this.fmt(peak.v)} · ${peak.n} tickets`,
        color: '#ff4d4d',
      });
    }

    // Top category
    if (by_family.length) {
      const top = by_family[0];
      const total = by_family.reduce((s, f) => s + f.v, 0);
      const pct = total > 0 ? Math.round((top.v / total) * 100) : 0;
      result.push({
        icon: '↑',
        text: `${top.label} lidera: ${pct}% de las ventas`,
        color: '#1a9e5a',
      });
    }

    // Top product
    if (top_products.length) {
      const top = top_products[0];
      result.push({
        icon: '🏆',
        text: `Producto estrella: ${top.name} (${top.units} uds · ${this.fmt(top.revenue)})`,
        color: '#d18a1c',
      });
    }

    // Revenue vs prev
    const { revenue, tickets, avg_ticket } = kpis;
    if (revenue.delta_pct !== 0) {
      const dir = revenue.delta_pct > 0 ? 'por encima' : 'por debajo';
      const sign = revenue.delta_pct > 0 ? '📈' : '📉';
      result.push({
        icon: sign,
        text: `Ventas ${Math.abs(revenue.delta_pct).toFixed(1)}% ${dir} del periodo anterior`,
        color: revenue.delta_pct > 0 ? '#1a9e5a' : '#ff4d4d',
      });
    }

    // Ticket medio insight
    if (avg_ticket.delta_pct !== 0) {
      const dir = avg_ticket.delta_pct > 0 ? 'sube' : 'baja';
      result.push({
        icon: '🎫',
        text: `Ticket medio ${dir} un ${Math.abs(avg_ticket.delta_pct).toFixed(1)}% (${this.fmt(avg_ticket.v)})`,
        color: '#0077cc',
      });
    }

    // Payment method diversity
    if (by_payment_method) {
      const methods = [
        { key: 'card', label: 'Tarjeta' },
        { key: 'cash', label: 'Efectivo' },
        { key: 'bizum', label: 'Bizum' },
      ];
      const total = methods.reduce((s, m) => s + (by_payment_method[m.key as keyof typeof by_payment_method] ?? 0), 0);
      if (total > 0) {
        const topMethod = methods
          .map(m => ({ ...m, v: by_payment_method[m.key as keyof typeof by_payment_method] ?? 0 }))
          .reduce((a, b) => a.v >= b.v ? a : b);
        const pct = Math.round((topMethod.v / total) * 100);
        result.push({
          icon: '💳',
          text: `${topMethod.label} domina los cobros: ${pct}% del total`,
          color: '#7857d6',
        });
      }
    }

    return result.slice(0, 5);
  });
}
