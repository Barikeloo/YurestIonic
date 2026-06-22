import { Component, computed, inject, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { BehaviorSubject, forkJoin, Observable, of, Subject, throwError } from 'rxjs';
import { catchError, finalize, switchMap, take, takeUntil, map, tap } from 'rxjs/operators';
import { AuthService } from '../../../../core/services/auth.service';
import { PinAuthService, AuthContext, AuthActionType } from '../../../../core/services/pin-auth.service';
import { PinAuthResult } from '../../../../components/pin-auth-modal/pin-auth-modal.component';
import { TpvService, TpvCashSession, TpvCashSessionListItem, TpvOrder, TpvOrderLine, TpvSale, TpvTableItem } from '../../services/tpv.service';
import { ChargeSessionService, ChargeSession, RecordPaymentResponse } from '../../services/charge-session.service';
import { CajaSessionFacade } from '../../facades/caja-session.facade';
import { CajaPaymentFacade } from '../../facades/caja-payment.facade';
import { OpenCashModalComponent } from '../../ui/open-cash-modal/open-cash-modal.component';
import { PinAuthModalComponent } from '../../../../components/pin-auth-modal/pin-auth-modal.component';
import { CashMovementModalComponent } from '../../ui/cash-movement-modal/cash-movement-modal.component';
import { ClosingWizardComponent, ZReportData } from '../../ui/closing-wizard/closing-wizard.component';
import { CobrarModalComponent, OrderLine } from '../../ui/cobrar-modal/cobrar-modal.component';
import { SplitBillModalComponent, BillLine } from '../../ui/split-bill-modal/split-bill-modal.component';
import { MesasAbiertasComponent, PendingTable } from '../../../tables/ui/mesas-abiertas/mesas-abiertas.component';
import { MethodBarComponent, MethodBreakdown } from '../../ui/method-bar/method-bar.component';
import { MovimientosListComponent, CashMovement } from '../../ui/movimientos-list/movimientos-list.component';
import { CardComponent } from '../../../../shared/components/card/card.component';
import { BadgeComponent } from '../../../../shared/components/badge/badge.component';
import { BtnComponent } from '../../../../shared/components/btn/btn.component';
import { KpiCardComponent } from '../../../../components/kpi-card/kpi-card.component';
import { SegmentComponent } from '../../../../shared/components/segment/segment.component';
import { PaymentSuccessComponent } from '../../ui/payment-success/payment-success.component';
import { DinersStatusComponent } from '../../../../shared/components/diners-status/diners-status.component';
import { CajaState } from '../../../../core/enums/caja-state.enum';
import { PaymentMethod } from '../../../../core/enums/payment-method.enum';
import { OrderStatus } from '../../../../core/enums/order-status.enum';
import { CashMovementType } from '../../../../core/enums/cash-movement-type.enum';
import { CashMovementReason } from '../../../../core/enums/cash-movement-reason.enum';
import { ToastService } from '../../../../core/services/toast.service';

interface LastClosedData {
  id: string;
  opened_by_user_id: string;
  closed_by_user_id: string | null;
  opened_at: string;
  closed_at: string | null;
  final_amount_cents: number | null;
  discrepancy_cents: number | null;
  discrepancy_reason: string | null;
  z_report_number: number | null;
  operator_name: string | null;
  tickets: number;
  diners: number;
}


interface CashMovementItem {
  uuid: string;
  type: CashMovementType;
  reason_code: string;
  amount_cents: number;
  description: string | null;
  user_id: string;
  created_at: string;
}

interface PendingTableRow {
  order_id: string;
  table_name: string;
  diners: number;
  opened_at: string;
  total: number;
}


@Component({
  selector: 'app-caja',
  templateUrl: './caja.page.html',
  styleUrls: ['./caja.page.scss'],
  imports: [
    OpenCashModalComponent,
    PinAuthModalComponent,
    CashMovementModalComponent,
    ClosingWizardComponent,
    CobrarModalComponent,
    SplitBillModalComponent,
    MesasAbiertasComponent,
    MethodBarComponent,
    MovimientosListComponent,
    CardComponent,
    BadgeComponent,
    BtnComponent,
    KpiCardComponent,
    PaymentSuccessComponent,
    SegmentComponent,
    DinersStatusComponent
  ],
  standalone: true,
  providers: [CajaSessionFacade, CajaPaymentFacade],
})
export class CajaPage implements OnInit, OnDestroy {
  protected readonly sessionFacade = inject(CajaSessionFacade);
  protected readonly paymentFacade = inject(CajaPaymentFacade);
  protected readonly tpvService = inject(TpvService);
  protected readonly authService = inject(AuthService);
  protected readonly pinAuthService = inject(PinAuthService);
  protected readonly chargeSessionService = inject(ChargeSessionService);
  protected readonly route = inject(ActivatedRoute);
  protected readonly router = inject(Router);
  protected readonly toastService = inject(ToastService);


  public readonly state = computed(() => this.sessionFacade.state());
  public readonly activeSession = computed(() => this.sessionFacade.activeSession());
  public readonly loading = computed(() => this.sessionFacade.loading());
  public readonly lastClosed = computed(() => this.sessionFacade.lastClosed());
  public readonly orphanSession = computed(() => this.sessionFacade.orphanSession());
  public readonly sessionSummary = computed(() => this.sessionFacade.sessionSummary());
  public readonly isClosingInProgress = computed(() => this.sessionFacade.isClosingInProgress());


  public readonly paymentState = computed(() => this.paymentFacade.paymentState());
  public readonly isProcessingPayment = computed(() => this.paymentFacade.isProcessing());
  public readonly currentChargeSession = computed(() => this.paymentFacade.chargeSession());
  public readonly currentDinerNumber = computed(() => this.paymentFacade.dinerNumber());
  public readonly loadedChargeSession = computed(() => this.paymentFacade.session());



  public readonly dinerAmountsByLines = computed<Record<number, number> | null>(() => {
    const session = this.loadedChargeSession();
    if (!session || !session.line_assignments?.length) return null;
    const amounts: Record<number, number> = {};
    for (const a of session.line_assignments) {
      const price = this.linePrices.get(a.order_line_id);
      if (price == null) continue;
      amounts[a.diner_number] = (amounts[a.diner_number] ?? 0) + price;
    }
    return Object.keys(amounts).length > 0 ? amounts : null;
  });

  public showOpenModal = false;
  public showPinAuthModal = false;
  public showPinAuthModalForCobrarMesa = false;
  public pendingTableToCharge: PendingTableRow | null = null;
  public showMovementModal = false;
  public showWizard = false;
  public currentTime = '';
  public closedSessions: TpvCashSessionListItem[] = [];
  public openCashError: string | null = null;
  public showCobrarModal = false;
  public showSplitModal = false;
  public showPaymentSuccess = false;
  public selectedTable: PendingTable | null = null;
  public selectedTableLines: OrderLine[] = [];

  private linePrices = new Map<string, number>();
  public currentUser: { id: string } | null = null;
  public fromMesas = false;
  public isPartialPayment = false;
  public paidDiners: number[] = [];


  public equalRoundPaidDinerNumbers: number[] = [];


  public equalRoundFixedPartCents: number | null = null;
  private lastPaymentWasEqualPart = false;
  private lastSplitOrderId: string | null = null;
  public originalOrderTotal = 0;
  public currentPaymentAmount = 0;
  public lastPaymentTicketText: string | null = null;
  public lastFinalTicketText: string | null = null;
  public lastPaymentSaleId: string | null = null;
  public lastFinalOrderId: string | null = null;
  public lastPaymentClosedOrder = false;

  private readonly pendingTables$ = new BehaviorSubject<PendingTableRow[]>([]);
  private readonly movements$ = new BehaviorSubject<CashMovementItem[]>([]);

  public get pendingTables(): PendingTableRow[] { return this.pendingTables$.value; }
  public get movements(): CashMovementItem[] { return this.movements$.value; }

  private setPendingTables(value: PendingTableRow[]): void { this.pendingTables$.next(value); }
  private setMovements(value: CashMovementItem[]): void { this.movements$.next(value); }

  private refreshInterval: any;
  private clockInterval: any;
  private readonly destroy$ = new Subject<void>();
  private readonly loadOrderDestroy$ = new Subject<void>();
  private readonly paymentFlowDestroy$ = new Subject<void>();
  public readonly deviceId: string;

  constructor() {
    this.deviceId = this.authService.getDeviceId();
  }



  private buildPaymentLineLabel(l: TpvOrderLine): {
    name: string;
    variantName: string | null;
    modifiers: { name: string; price: number }[] | null;
  } {
    if (l.menu_id) {
      const menuMods: { name: string; price: number }[] = [];
      for (const sel of l.menu_selections ?? []) {
        const baseLabel = sel.section_name ? `${sel.section_name}: ${sel.product_name}` : sel.product_name;
        const variantSuffix = sel.variant_name ? ` (${sel.variant_name})` : '';
        menuMods.push({ name: baseLabel + variantSuffix, price: sel.extra_price ?? 0 });
        for (const mod of sel.modifiers ?? []) {
          menuMods.push({ name: `   · ${mod.name}`, price: mod.price ?? 0 });
        }
      }
      return {
        name: l.menu_name ?? 'Menú',
        variantName: null,
        modifiers: menuMods.length > 0 ? menuMods : null,
      };
    }
    return {
      name: l.product_name ?? 'Producto',
      variantName: l.variant_name ?? null,
      modifiers: l.modifiers?.map((m) => ({ name: m.name, price: m.price })) ?? null,
    };
  }

  public ngOnInit(): void {
    this.updateClock();
    this.clockInterval = setInterval(() => this.updateClock(), 1000);
    this.authService.currentUser$.pipe(takeUntil(this.destroy$)).subscribe((user) => { this.currentUser = user; });
    this.loadActiveSession();

    this.route.queryParams.pipe(takeUntil(this.destroy$)).subscribe((params) => {
      if (params['orderId'] && params['fromMesas'] === 'true') {
        this.loadOrderForPayment(params['orderId']);
        this.router.navigate([], {
          relativeTo: this.route,
          queryParams: {},
          replaceUrl: true,
        });
      }
    });
  }

  public ngOnDestroy(): void {
    this.stopRefreshInterval();
    if (this.clockInterval) clearInterval(this.clockInterval);
    this.destroy$.next();
    this.destroy$.complete();
    this.pendingTables$.complete();
    this.movements$.complete();
  }

  private loadOrderForPayment(orderId: string): void {
    this.loadOrderDestroy$.next();
    this.resetPaymentState();
    this.fromMesas = true;

    this.tpvService.getOrder(orderId).pipe(
      takeUntil(this.destroy$),
      takeUntil(this.loadOrderDestroy$),
      switchMap((order) =>
        this.tpvService.listTables().pipe(
          takeUntil(this.loadOrderDestroy$),
          map((tables) => ({ order, tables }))
        )
      ),
      switchMap(({ order, tables }) => {
        const table = tables.find((t) => t.id === order.table_id);
        const tableName = table?.name || order.table_id || 'Mesa';

        return forkJoin({
          lines: this.tpvService.getOrderLines(orderId).pipe(takeUntil(this.loadOrderDestroy$)),
          orderTotal: this.tpvService.getOrderTotal(orderId).pipe(takeUntil(this.loadOrderDestroy$)),
        }).pipe(
          takeUntil(this.loadOrderDestroy$),
          map(({ lines, orderTotal }) => ({ order, tableName, lines, orderTotal }))
        );
      }),
      switchMap(({ order, tableName, lines, orderTotal }) => {
        const originalTotal = orderTotal.total_cents;
        this.originalOrderTotal = originalTotal;

        return this.tpvService.getOrderPaidTotal(orderId).pipe(
          takeUntil(this.loadOrderDestroy$),
          map((paidResponse) => ({
            order,
            tableName,
            lines,
            originalTotal,
            paidTotal: paidResponse.total_cents,
          })),
          catchError((error) => {
            console.error('Error fetching paid total:', error);
            return of({
              order,
              tableName,
              lines,
              originalTotal,
              paidTotal: 0,
            });
          })
        );
      }),
      catchError((error) => {
        console.error('Error in loadOrderForPayment chain:', error);
        return throwError(() => error);
      })
    ).subscribe({
      next: ({ order, tableName, lines, originalTotal, paidTotal }) => {
        const remainingTotal = Math.max(0, originalTotal - paidTotal);

        const diners = order.diners ?? 1;
        const partAmount = diners > 1 ? Math.floor(originalTotal / diners) : originalTotal;
        const paidDinersCount = diners > 1 && partAmount > 0 ? Math.floor(paidTotal / partAmount) : (paidTotal > 0 ? 1 : 0);
        this.paidDiners = Array.from({ length: paidDinersCount }, (_, i) => i + 1);

        this.selectedTable = {
          order_id: orderId,
          table_name: tableName,
          total: remainingTotal,
          status: order.status,
          diners: diners,
          opened_at: order.opened_at || new Date().toISOString(),
        } as PendingTable;
        this.selectedTableLines = lines.map((l) => {
          const label = this.buildPaymentLineLabel(l);
          return {
            id: l.id,
            name: label.name,
            price: l.price * l.quantity,
            unitPrice: l.price,
            quantity: l.quantity,
            taxPercentage: l.tax_percentage,
            variantName: label.variantName,
            modifiers: label.modifiers,
          };
        });
        this.currentPaymentAmount = remainingTotal;
        this.showCobrarModal = true;
      },
      error: (error) => {
        console.error('Error loading order for payment:', error);
        this.selectedTableLines = [];
        this.currentPaymentAmount = 0;
        this.showCobrarModal = false;
      },
    });
  }

  private loadActiveSession(): void {
    this.sessionFacade.loadActiveSession(this.deviceId).pipe(takeUntil(this.destroy$)).subscribe({
      next: (session) => {
        if (session === null) {
          this.sessionFacade.setState(CajaState.PRE_APERTURA);
          this.loadLastClosedData();
          this.stopRefreshInterval();
        } else {
          this.sessionFacade.setLoading(false);
          switch (session.status) {
            case 'open':
              this.sessionFacade.setState(CajaState.ACTIVA);
              this.loadSessionSummary();
              this.loadActiveDashboardData();
              this.startRefreshInterval();
              break;
            case 'closing':
              this.sessionFacade.setState(CajaState.ARQUEO);
              this.showWizard = true;
              this.loadSessionSummary();
              this.stopRefreshInterval();
              break;
            case 'closed':
            case 'abandoned':
              this.sessionFacade.setState(CajaState.HISTORICO);
              this.loadClosedSessions();
              this.stopRefreshInterval();
              break;
          }
        }
      },
      error: () => {
        this.sessionFacade.setLoading(false);
        this.sessionFacade.setState(CajaState.PRE_APERTURA);
        this.loadLastClosedData();
      },
    });
  }

  private updateClock(): void {
    this.currentTime = new Date().toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  }

  private loadSessionSummary(): void {
    if (!this.activeSession()) return;
    this.sessionFacade.loadSessionSummary(this.activeSession()!.uuid).pipe(takeUntil(this.destroy$)).subscribe({
      next: (summary) => {
        this.sessionFacade.setSessionSummary(summary);
        this.loadActiveDashboardData();
      },
      error: (error) => { console.error('Error loading session summary:', error); },
    });
  }

  private loadActiveDashboardData(): void {
    if (!this.activeSession()) return;
    const sessionUuid = this.activeSession()!.uuid;
    this.sessionFacade.listCashMovements(sessionUuid).pipe(takeUntil(this.destroy$)).subscribe({
      next: (response) => { this.setMovements(response.movements as CashMovementItem[]); },
      error: () => { this.setMovements([]); },
    });

    forkJoin({
      orders: this.tpvService.listOrders().pipe(catchError(() => of([] as TpvOrder[]))),
      tables: this.tpvService.listTables().pipe(catchError(() => of([] as TpvTableItem[]))),
    }).pipe(takeUntil(this.destroy$)).subscribe(({ orders, tables }) => {
      const tableNameById = new Map(tables.map((t) => [t.id, t.name] as const));
      const activeOrders = orders.filter((o) => o.status === OrderStatus.OPEN || o.status === OrderStatus.TO_CHARGE);

      if (activeOrders.length === 0) {
        this.setPendingTables([]);
        return;
      }

      const paidTotals$ = activeOrders.map((o) =>
        this.tpvService.getOrderPaidTotal(o.id).pipe(
          map((r) => ({ orderId: o.id, paid: r.total_cents })),
          catchError(() => of({ orderId: o.id, paid: 0 })),
        ),
      );

      forkJoin(paidTotals$).pipe(takeUntil(this.destroy$)).subscribe((paidEntries) => {
        const paidByOrder = new Map(paidEntries.map((e) => [e.orderId, e.paid] as const));
        this.setPendingTables(activeOrders.map((o) => ({
          order_id: o.id,
          table_name: tableNameById.get(o.table_id) ?? 'Mesa',
          diners: o.diners,
          opened_at: o.opened_at,
          total: Math.max(0, o.total - (paidByOrder.get(o.id) ?? 0)),
        })));
      });
    });
  }

  private startRefreshInterval(): void {
    this.stopRefreshInterval();
    this.refreshInterval = setInterval(() => this.loadSessionSummary(), 3000);
  }

  private stopRefreshInterval(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
      this.refreshInterval = null;
    }
  }

  public onOpenModal(): void {
    if (this.pinAuthService.requiresPin(AuthActionType.CRITICAL)) {
      this.showPinAuthModal = true;
    } else {
      this.showOpenModal = true;
    }
  }

  public onPinAuthenticated(result: PinAuthResult): void {
    this.pinAuthService.setAuthContext({
      userId: result.userId,
      userName: result.userName,
      userRole: result.userRole,
      authenticatedAt: Date.now(),
      lastActivityAt: Date.now(),
    });
    this.showPinAuthModal = false;
    this.showOpenModal = true;
  }

  public onOpenCash(data: { userId: string; initialAmountCents: number; notes?: string }): void {
    this.openCashError = null;
    this.sessionFacade.openSession({
      device_id: this.deviceId,
      opened_by_user_id: data.userId,
      initial_amount_cents: data.initialAmountCents,
      notes: data.notes,
    }).pipe(takeUntil(this.destroy$)).subscribe({
      next: (session) => {
        this.showOpenModal = false;
        this.sessionFacade.setActiveSession(session);
        this.sessionFacade.setState(CajaState.ACTIVA);
        this.loadSessionSummary();
        this.startRefreshInterval();
      },
      error: (error) => {
        this.openCashError = error.message;
        this.showOpenModal = false;
      },
    });
  }

  public onOpenMovementModal(): void {
    this.showMovementModal = true;
  }

  public onRegisterMovement(data: {
    type: CashMovementType;
    reasonCode: CashMovementReason;
    amountCents: number;
    description?: string;
  }): void {
    if (!this.activeSession()) return;
    this.sessionFacade.registerMovement({
      cash_session_id: this.activeSession()!.uuid,
      type: data.type,
      reason_code: data.reasonCode,
      amount_cents: data.amountCents,
      user_id: this.activeSession()!.opened_by_user_id ?? '',
      description: data.description,
    }).pipe(takeUntil(this.destroy$)).subscribe({
      next: () => {
        this.showMovementModal = false;
        this.loadSessionSummary();
      },
      error: (error) => { this.toastService.presentError('Error al registrar el movimiento: ' + error.message); },
    });
  }

  public onStartClosing(): void {
    if (!this.activeSession()) return;
    this.sessionFacade.startClosing({ cash_session_id: this.activeSession()!.uuid }).pipe(takeUntil(this.destroy$)).subscribe({
      next: (response) => {
        this.sessionFacade.setState(CajaState.ARQUEO);
        this.showWizard = true;
        this.sessionFacade.setActiveSession({ ...this.activeSession()!, status: response.status as 'open' | 'closing' | 'closed' | 'abandoned' });
        this.loadSessionSummary();
        this.stopRefreshInterval();
      },
      error: (error) => { this.toastService.presentError('Error al iniciar cierre: ' + error.message); },
    });
  }

  public onCancelClosing(): void {
    if (!this.activeSession()) return;
    if (this.activeSession()!.status !== 'closing') {
      this.toastService.presentWarning('Solo se puede cancelar el cierre cuando la sesión está en proceso de cierre.');
      return;
    }
    this.sessionFacade.cancelClosing({ cash_session_id: this.activeSession()!.uuid }).pipe(takeUntil(this.destroy$)).subscribe({
      next: (response) => {
        this.sessionFacade.setState(CajaState.ACTIVA);
        this.showWizard = false;
        this.sessionFacade.setActiveSession({ ...this.activeSession()!, status: response.status as 'open' | 'closing' | 'closed' | 'abandoned' });
        this.startRefreshInterval();
      },
      error: (error) => { this.toastService.presentError('Error al cancelar el cierre: ' + error.message); },
    });
  }

  public onWizardClose(): void {
    this.showWizard = false;
    if (this.isClosingInProgress()) {
      console.log('Closing is in progress, skipping cancel');
      return;
    }
    if (this.sessionFacade.state() === CajaState.ACTIVA || this.sessionFacade.state() === CajaState.ARQUEO) {
      this.tpvService.cancelClosingCashSession({ cash_session_id: this.activeSession()!.uuid }).pipe(takeUntil(this.destroy$)).subscribe({
        next: (response) => {
          this.sessionFacade.setState(CajaState.ACTIVA);
          this.sessionFacade.setActiveSession({ ...this.activeSession()!, status: response.status as 'open' | 'closing' | 'closed' | 'abandoned' });
          this.startRefreshInterval();
        },
        error: (error) => {
          console.error('Error al cancelar el cierre:', error);
          this.sessionFacade.setState(CajaState.ACTIVA);
          this.startRefreshInterval();
        },
      });
    } else if (this.state() === CajaState.ARQUEO) {
      this.sessionFacade.setState(CajaState.ACTIVA);
      this.startRefreshInterval();
    }
  }

  public onCompleteClosing(data: { countedAmount: number; discrepancyReason?: string }): void {
    if (!this.activeSession()) return;

    if (this.activeSession()!.status !== 'closing') {
      console.warn('Session is not in closing status, reloading active session...');
      this.tpvService.getActiveCashSession(this.deviceId).pipe(takeUntil(this.destroy$)).subscribe({
        next: (session) => {
          if (session && session.status === 'closing') {
            this.sessionFacade.setActiveSession(session);
            this.proceedWithClose(data);
          } else {
            this.toastService.presentError('La sesión no está en estado de cierre. Por favor, intente iniciar el cierre nuevamente.');
            if (session) {
              this.sessionFacade.setActiveSession(session);
              this.sessionFacade.setState(session.status === 'open' ? CajaState.ACTIVA : CajaState.ARQUEO);
            }
          }
        },
        error: () => {
          this.toastService.presentError('Error al verificar el estado de la caja.');
        },
      });
      return;
    }

    this.proceedWithClose(data);
  }

  private proceedWithClose(data: { countedAmount: number; discrepancyReason?: string }): void {
    if (!this.activeSession()) return;
    this.sessionFacade.closeSession({
      cash_session_id: this.activeSession()!.uuid,
      closed_by_user_id: this.activeSession()!.opened_by_user_id ?? '',
      final_amount_cents: data.countedAmount,
      discrepancy_reason: data.discrepancyReason,
    }).pipe(takeUntil(this.destroy$)).subscribe({
      next: () => {
        this.showWizard = false;
        this.sessionFacade.setActiveSession(null);
        this.sessionFacade.setState(CajaState.HISTORICO);
        this.loadClosedSessions();
      },
      error: (error) => {
        this.toastService.presentError('Error al cerrar la caja: ' + error.message);
      },
    });
  }

  public get wizardExpectedAmount(): number {
    return this.sessionSummary()?.expected_amount ?? 0;
  }

  public get wizardZData(): ZReportData | null {
    if (!this.sessionSummary()) return null;
    return {
      tickets: this.sessionSummary()!.payments_count,
      diners: 0,
      gross: this.sessionSummary()!.total_sales,
      discounts: 0,
      net: this.sessionSummary()!.total_sales,
      cash: this.sessionSummary()!.total_cash_payments,
      card: this.sessionSummary()!.total_card_payments,
      bizum: this.sessionSummary()!.total_bizum_payments,
      invitation: this.sessionSummary()!.total_other_payments,
      invitations: 0,
      invValue: 0,
      cancellations: 0,
      tipsCard: 0,
      initial: this.sessionSummary()!.initial_amount_cents,
      movIn: this.sessionSummary()!.total_in_movements,
      movOut: this.sessionSummary()!.total_out_movements,
    };
  }

  private loadLastClosedData(): void {
    this.sessionFacade.loadLastClosedSession().pipe(takeUntil(this.destroy$)).subscribe({
      next: (data) => {
        this.sessionFacade.setLastClosed(data?.last_closed || null);
        this.sessionFacade.setOrphanSession(data?.orphan_session || null);
        this.sessionFacade.setLoading(false);
      },
      error: () => { this.sessionFacade.setLoading(false); },
    });
  }

  private loadClosedSessions(): void {
    this.sessionFacade.loadClosedSessions().pipe(takeUntil(this.destroy$)).subscribe({
      next: (data) => { this.closedSessions = data.sessions; },
      error: (error) => { console.error('Error loading sessions:', error); },
    });
  }

  public formatCents(cents: number | null | undefined): string {
    if (cents == null) return '-';
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-ES');
  }

  public formatShortDate(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
    });
  }

  public formatTime(dateString: string | null | undefined): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('es-ES', {
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  public formatCentsSigned(cents: number | null | undefined): string {
    if (cents == null) return '0,00';
    const value = cents / 100;
    const formatted = Math.abs(value).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return value < 0 ? `− ${formatted}` : `+ ${formatted}`;
  }

  public formatAverageTicket(lastClosed: LastClosedData | null): string {
    if (!lastClosed || !lastClosed.tickets || lastClosed.tickets === 0) return '0,00';
    const finalAmount = lastClosed.final_amount_cents ?? 0;
    const average = finalAmount / lastClosed.tickets / 100;
    return average.toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  public get movementsList(): CashMovement[] {
    return this.movements.map((m) => ({
      id: m.uuid,
      type: m.type,
      reason: m.reason_code,
      time: new Date(m.created_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
      user: m.user_id,
      amount: m.amount_cents,
    }));
  }

  public get paymentMethods(): MethodBreakdown {
    if (!this.sessionSummary()) return {};
    return {
      cash: this.sessionSummary()!.total_cash_payments || 0,
      card: this.sessionSummary()!.total_card_payments || 0,
      bizum: this.sessionSummary()!.total_bizum_payments || 0,
      transfer: this.sessionSummary()!.total_other_payments || 0,
    };
  }

  public onCobrarMesa(mesa: PendingTable): void {
    this.pendingTableToCharge = mesa;

    if (this.pinAuthService.requiresPin(AuthActionType.NORMAL)) {
      this.showPinAuthModalForCobrarMesa = true;
    } else {
      this.loadOrderForCobrarMesa();
    }
  }

  private loadOrderForCobrarMesa(): void {
    const mesa = this.pendingTableToCharge;
    if (!mesa) return;

    this.paymentFlowDestroy$.next();
    this.resetPaymentState();

    this.selectedTable = mesa;
    this.isPartialPayment = false;

    forkJoin({
      order: this.tpvService.getOrder(mesa.order_id).pipe(takeUntil(this.paymentFlowDestroy$)),
      orderTotal: this.tpvService.getOrderTotal(mesa.order_id).pipe(takeUntil(this.paymentFlowDestroy$)),
    }).pipe(
      takeUntil(this.destroy$),
      takeUntil(this.paymentFlowDestroy$),
      switchMap(({ order, orderTotal }) => {
        console.log('Order loaded:', order);
        const originalTotal = orderTotal.total_cents;
        this.originalOrderTotal = originalTotal;

        return this.tpvService.getOrderPaidTotal(mesa.order_id).pipe(
          takeUntil(this.paymentFlowDestroy$),
          map((paidResponse) => ({
            originalTotal,
            paidTotal: paidResponse.total_cents,
            fallback: false,
          })),
          catchError((error) => {
            console.error('Error fetching paid total:', error);
            return of({ originalTotal, paidTotal: 0, fallback: true });
          })
        );
      }),
      switchMap(({ originalTotal, paidTotal, fallback }) => {
        const remainingTotal = Math.max(0, originalTotal - paidTotal);
        const diners = mesa.diners ?? 1;

        if (!fallback) {
          const partAmount = diners > 1 ? Math.floor(originalTotal / diners) : originalTotal;
          const paidDinersCount = diners > 1 && partAmount > 0 ? Math.floor(paidTotal / partAmount) : (paidTotal > 0 ? 1 : 0);
          this.paidDiners = Array.from({ length: paidDinersCount }, (_, i) => i + 1);
        } else {
          this.paidDiners = [];
        }

        if (this.selectedTable) {
          this.selectedTable.total = remainingTotal;
        }

        console.log('Order total:', originalTotal, 'Paid:', paidTotal, 'Remaining:', remainingTotal);
        console.log('Calculated paidDiners:', this.paidDiners);
        console.log('Loading order lines for order_id:', mesa.order_id);

        return this.tpvService.getOrderLines(mesa.order_id).pipe(
          takeUntil(this.paymentFlowDestroy$),
          map((lines) => ({
            lines: lines.map((l) => {
              const label = this.buildPaymentLineLabel(l);
              return {
                id: l.id,
                name: label.name,
                price: l.price * l.quantity,
                unitPrice: l.price,
                quantity: l.quantity,
                taxPercentage: l.tax_percentage,
                variantName: label.variantName,
                modifiers: label.modifiers,
              };
            }),
            remainingTotal,
          })),
          catchError((error) => {
            console.error('Error loading order lines:', error);
            return of({ lines: [] as OrderLine[], remainingTotal });
          })
        );
      }),
      catchError((error) => {
        console.error('Error in payment flow chain:', error);
        this.toastService.presentError('No se pudo cargar la orden.');
        this.pendingTableToCharge = null;
        return throwError(() => error);
      })
    ).subscribe({
      next: ({ lines, remainingTotal }) => {
        this.selectedTableLines = lines;
        this.captureLinePrices(lines);
        this.currentPaymentAmount = remainingTotal;
        this.showCobrarModal = true;
        this.pendingTableToCharge = null;
      },
      error: () => {
      },
    });
  }

  public onPinAuthenticatedForCobrarMesa(result: PinAuthResult): void {
    const now = Date.now();
    this.pinAuthService.setAuthContext({
      userId: result.userId,
      userName: result.userName,
      userRole: result.userRole,
      authenticatedAt: now,
      lastActivityAt: now,
    });
    this.showPinAuthModalForCobrarMesa = false;
    this.loadOrderForCobrarMesa();
  }

  public onSplitMesa(mesa: PendingTable): void {
    this.paymentFlowDestroy$.next();
    this.resetPaymentState();
    this.selectedTable = mesa;

    forkJoin({
      order: this.tpvService.getOrder(mesa.order_id).pipe(takeUntil(this.paymentFlowDestroy$)),
      orderTotal: this.tpvService.getOrderTotal(mesa.order_id).pipe(takeUntil(this.paymentFlowDestroy$)),
    }).pipe(
      takeUntil(this.destroy$),
      takeUntil(this.paymentFlowDestroy$),
      switchMap(({ order, orderTotal }) => {
        const originalTotal = orderTotal.total_cents;
        this.originalOrderTotal = originalTotal;

        return this.tpvService.getOrderPaidTotal(mesa.order_id).pipe(
          takeUntil(this.paymentFlowDestroy$),
          map((paidResponse) => ({
            originalTotal,
            paidTotal: paidResponse.total_cents,
            fallback: false,
          })),
          catchError((error) => {
            console.error('Error fetching paid total:', error);
            return of({ originalTotal, paidTotal: 0, fallback: true });
          })
        );
      }),
      switchMap(({ originalTotal, paidTotal, fallback }) => {
        const remainingTotal = Math.max(0, originalTotal - paidTotal);

        this.paidDiners = [];

        if (this.selectedTable) {
          this.selectedTable.total = remainingTotal;
        }

        console.log('Order total:', originalTotal, 'Paid:', paidTotal, 'Remaining:', remainingTotal);
        console.log('Calculated paidDiners:', this.paidDiners);
        console.log('Loading order lines for order_id:', mesa.order_id);

        return this.tpvService.getOrderLines(mesa.order_id).pipe(
          takeUntil(this.paymentFlowDestroy$),
          map((lines) => ({
            lines: lines.map((l) => {
              const label = this.buildPaymentLineLabel(l);
              return {
                id: l.id,
                name: label.name,
                price: l.price * l.quantity,
                diner: l.diner_number,
                variantName: label.variantName,
                modifiers: label.modifiers?.map((m) => ({ name: m.name })) ?? null,
              };
            }),
            remainingTotal,
          })),
          catchError((error) => {
            console.error('Error loading order lines:', error);
            return of({ lines: [] as OrderLine[], remainingTotal });
          })
        );
      }),
      catchError((error) => {
        console.error('Error in split mesa flow:', error);
        this.selectedTableLines = [];
        this.showSplitModal = true;
        return throwError(() => error);
      })
    ).subscribe({
      next: ({ lines }) => {
        this.selectedTableLines = lines;
        this.captureLinePrices(lines);
        this.showSplitModal = true;
      },
      error: () => {
      },
    });
  }

  public onSplitBill(): void {
    this.showCobrarModal = false;

    const currentOrderId = this.selectedTable?.order_id ?? null;
    if (currentOrderId !== this.lastSplitOrderId) {
      this.equalRoundPaidDinerNumbers = [];
      this.equalRoundFixedPartCents = null;
      this.lastSplitOrderId = currentOrderId;
    }

    if (!this.selectedTable) {
      this.showSplitModal = true;
      return;
    }

    const orderId = this.selectedTable.order_id;

    forkJoin({
      orderTotal: this.tpvService.getOrderTotal(orderId).pipe(
        take(1),
        catchError(() => of({ total_cents: this.originalOrderTotal || 0 }))
      ),
      paidTotal: this.tpvService.getOrderPaidTotal(orderId).pipe(
        take(1),
        catchError(() => of({ total_cents: 0 }))
      ),
      chargeSession: this.paymentFacade.getCurrentChargeSession(orderId).pipe(
        take(1),
        catchError((error) => {
          if (error.status === 404) return of(null);
          console.error('Error fetching charge session in onSplitBill:', error);

          return of(this.loadedChargeSession());
        })
      ),

      orderLines: this.tpvService.getOrderLines(orderId).pipe(
        take(1),
        catchError(() => of([])),
      ),
    }).pipe(
      takeUntil(this.destroy$),
      finalize(() => {
        if (!this.showSplitModal) {
          console.warn('onSplitBill finalize fallback: forcing showSplitModal = true');
          this.showSplitModal = true;
        }
      })
    ).subscribe({
      next: ({ orderTotal, paidTotal, chargeSession, orderLines }) => {
        const paidTotalCents = paidTotal.total_cents;
        const originalTotal = orderTotal.total_cents;

        this.selectedTableLines = (orderLines ?? []).map((l) => {
          const label = this.buildPaymentLineLabel(l);
          return {
            id: l.id,
            name: label.name,
            price: l.price * l.quantity,
            unitPrice: l.price,
            quantity: l.quantity,
            taxPercentage: l.tax_percentage,
            diner: l.diner_number,
            variantName: label.variantName,
            modifiers: label.modifiers,
          };
        });
        this.captureLinePrices(this.selectedTableLines);

        this.originalOrderTotal = originalTotal;


        const loaded = this.loadedChargeSession();
        const useSession = chargeSession && loaded
          ? (new Date(chargeSession.updated_at) >= new Date(loaded.updated_at)
            ? chargeSession
            : loaded)
          : (chargeSession ?? loaded ?? null);

        this.paymentFacade.setLoadedChargeSession(useSession);
        console.log('onSplitBill - chargeSession loaded:', useSession);
        console.log('[CajaPage] onSplitBill - paid_diner_numbers:', useSession?.paid_diner_numbers);
        console.log('[CajaPage] onSplitBill - remaining_cents:', useSession?.remaining_cents);

        if (useSession) {
          this.paidDiners = useSession.paid_diner_numbers;
          console.log('onSplitBill - synced paidDiners:', this.paidDiners, 'remaining_cents:', useSession.remaining_cents);
          if (this.selectedTable) {
            this.selectedTable.total = useSession.remaining_cents;
            this.updatePendingTableTotal(this.selectedTable.order_id, useSession.remaining_cents);
          }
        } else {

          if (!loaded) {
            this.paidDiners = [];
          }
          if (this.selectedTable) {
            const remaining = Math.max(0, originalTotal - paidTotalCents);
            this.selectedTable.total = remaining;
            this.updatePendingTableTotal(this.selectedTable.order_id, remaining);
          }
        }

        console.log('onSplitBill - opening modal with loadedChargeSession:', this.loadedChargeSession);
        this.showSplitModal = true;
      },
      error: (error) => {
        console.error('Error in onSplitBill:', error);
        this.showSplitModal = true;
      },
    });
  }

  public onConfirmPayment(data: { method: string; amount: number; tip?: number; isManualPartial?: boolean }): void {
    if (this.isProcessingPayment()) {
      console.log('Payment already in progress, ignoring double click');
      return;
    }

    console.log('Payment confirmed:', data);
    console.log('Selected table:', this.selectedTable);
    console.log('Current user:', this.currentUser);
    console.log('Selected table lines:', this.selectedTableLines);
    console.log('From mesas:', this.fromMesas);

    if (!this.selectedTable) {
      console.error('No selected table');
      this.toastService.presentError('No hay mesa seleccionada');
      return;
    }

    if (!this.currentUser) {
      console.error('No current user');
      this.toastService.presentError('No hay usuario actual');
      return;
    }

    if (!this.selectedTable?.order_id) {
      console.error('No order_id in selected table');
      this.toastService.presentError('No hay orden seleccionada');
      return;
    }

    this.paymentFacade.setIsProcessingPayment(true);

    const payments = [
      {
        method: data.method,
        amount_cents: data.amount,
        metadata: data.tip ? { tip_cents: data.tip } : undefined,
      },
    ];

    let willBeComplete = false;
    if (this.isPartialPayment && this.selectedTable && !data.isManualPartial) {
      const diners = this.selectedTable.diners ?? 1;
      const paidDinersCount = this.paidDiners.length;
      willBeComplete = diners === 1 || paidDinersCount === diners - 1;
      console.log('Partial payment - Diners:', diners, 'Paid diners:', paidDinersCount, 'Will be complete:', willBeComplete);
    } else {
      const currentPaid = this.originalOrderTotal - (this.selectedTable?.total || 0);
      willBeComplete = (currentPaid + data.amount) >= this.originalOrderTotal;
    }

    const orderLineIds = (willBeComplete || data.isManualPartial)
      ? undefined
      : this.selectedTableLines
          .filter((l) => l.id)
          .map((l) => l.id) as string[];

    console.log('Order line IDs:', orderLineIds);

    console.log('Payment calculation - This payment:', data.amount, 'Original total:', this.originalOrderTotal, 'Will be complete:', willBeComplete);

    let isPartialPayment = this.isPartialPayment || !!data.isManualPartial;
    if (this.fromMesas || data.isManualPartial) {
      isPartialPayment = !willBeComplete;
    }

    console.log('Creating sale with payload:', {
      order_id: this.selectedTable.order_id,
      opened_by_user_id: this.currentUser.id,
      closed_by_user_id: this.currentUser.id,
      device_id: this.deviceId,
      payments,
      order_line_ids: orderLineIds,
      is_partial_payment: isPartialPayment,
    });

    this.paymentFlowDestroy$.next();

    const orderId = this.selectedTable?.order_id;
    const activeSessionId = this.loadedChargeSession()?.status === 'active'
      ? this.loadedChargeSession()?.id
      : null;

    console.log('onConfirmPayment - activeSessionId:', activeSessionId, 'loadedChargeSession:', this.loadedChargeSession(), 'currentDinerNumber:', this.currentDinerNumber());

    const paymentMethod = payments[0].method;
    const directMethods = [PaymentMethod.CASH, PaymentMethod.CARD, PaymentMethod.BIZUM, PaymentMethod.VOUCHER, PaymentMethod.INVITATION];
    const mappedMethod: PaymentMethod = directMethods.includes(paymentMethod as PaymentMethod)
      ? (paymentMethod as PaymentMethod)
      : PaymentMethod.OTHER;

    const sale$: Observable<RecordPaymentResponse | TpvSale> = activeSessionId
      ? this.paymentFacade.recordPayment(activeSessionId, {
          payment_method: mappedMethod,
          amount_cents: data.amount,
          diner_number: this.currentDinerNumber() || undefined,
          opened_by_user_id: this.currentUser?.id ?? '',
          closed_by_user_id: this.currentUser?.id ?? '',
          device_id: this.deviceId,
        })
      : this.tpvService.createSale({
          order_id: this.selectedTable!.order_id,
          opened_by_user_id: this.currentUser!.id,
          closed_by_user_id: this.currentUser!.id,
          device_id: this.deviceId,
          payments,
          order_line_ids: orderLineIds,
          is_partial_payment: isPartialPayment,
        });

    console.log('Using', activeSessionId ? 'recordPayment' : 'createSale', 'with diner_number:', this.currentDinerNumber());

    sale$.pipe(
      takeUntil(this.destroy$),
      takeUntil(this.paymentFlowDestroy$),
      tap((result) => {
        const saleId = 'payment_id' in result ? result.payment_id : result.id;
        this.printPaymentTicket(saleId);
      }),
      switchMap((result) => {
        console.log('Payment registered:', result);
        this.showCobrarModal = false;

        if (activeSessionId) {
          const paymentResponse = result as RecordPaymentResponse;
          return this.paymentFacade.getCurrentChargeSession(orderId!).pipe(
            takeUntil(this.paymentFlowDestroy$),
            map((freshSession) => {
              console.log('Charge session reloaded after payment:', freshSession);
              this.paymentFacade.setLoadedChargeSession(freshSession);
              this.paidDiners = freshSession.paid_diner_numbers;
              console.log('[CajaPage] Payment done - freshSession.paid_diner_numbers:', freshSession.paid_diner_numbers);
              console.log('[CajaPage] Payment done - freshSession.remaining_cents:', freshSession.remaining_cents);


              const dinerNum = this.currentDinerNumber();
              if (this.lastPaymentWasEqualPart && dinerNum !== null) {
                if (!this.equalRoundPaidDinerNumbers.includes(dinerNum)) {
                  this.equalRoundPaidDinerNumbers = [...this.equalRoundPaidDinerNumbers, dinerNum];
                  console.log('[CajaPage] Tracked equal-round paid diner:', dinerNum);
                }
              } else if (this.equalRoundFixedPartCents !== null) {

                this.equalRoundFixedPartCents = null;
                console.log('[CajaPage] Equal round quota invalidated by non-equal payment');
              }
              this.lastPaymentWasEqualPart = false;

              if (this.selectedTable) {
                this.selectedTable.total = freshSession.remaining_cents;
                this.updatePendingTableTotal(this.selectedTable.order_id, freshSession.remaining_cents);
              }
              this.paymentFacade.setCurrentChargeSession(null);
              this.paymentFacade.setCurrentDinerNumber(null);
              return { type: 'charge_session' as const, isOrderComplete: paymentResponse.is_session_complete };
            }),
            catchError((error) => {
              console.warn('Could not reload charge session after payment:', error);
              this.paymentFacade.setCurrentChargeSession(null);
              this.paymentFacade.setCurrentDinerNumber(null);
              return of({
                type: 'charge_session' as const,
                isOrderComplete: paymentResponse.is_session_complete,
              });
            })
          );
        }

        if (isPartialPayment && orderId) {
          return this.tpvService.getOrderPaidTotal(orderId).pipe(
            takeUntil(this.paymentFlowDestroy$),
            map((paidResponse) => {
              const paidTotal = paidResponse.total_cents;
              const originalTotal = this.originalOrderTotal || this.selectedTable?.total || 0;

              this.paidDiners = [];
              console.log('Reset paidDiners for manual partial payment');

              const isOrderComplete = paidTotal >= originalTotal;

              const isManual = !!data.isManualPartial;

              return { type: 'partial' as const, isOrderComplete, orderId, isManual };
            }),
            catchError((error) => {
              console.error('Error fetching paid total for diner calculation:', error);
              return of({ type: 'partial' as const, isOrderComplete: false, orderId, isManual: !!data.isManualPartial });
            })
          );
        }

        if (orderId) {
          return forkJoin({
            paidTotal: this.tpvService.getOrderPaidTotal(orderId).pipe(takeUntil(this.paymentFlowDestroy$)),
            orderTotal: this.tpvService.getOrderTotal(orderId).pipe(takeUntil(this.paymentFlowDestroy$)),
          }).pipe(
            map(({ paidTotal, orderTotal }) => {
              const isOrderComplete = paidTotal.total_cents >= orderTotal.total_cents;
              return { type: 'full' as const, isOrderComplete };
            }),
            catchError(() => {
              return of({ type: 'full' as const, isOrderComplete: willBeComplete });
            })
          );
        }

        return of({ type: 'full' as const, isOrderComplete: willBeComplete });
      }),
      catchError((error) => {
        console.error('Error creating sale:', error);
        this.toastService.presentError('Error al crear la venta: ' + (error.message || 'Error desconocido'));
        this.paymentFacade.setIsProcessingPayment(false);
        return throwError(() => error);
      })
    ).subscribe({
      next: (result) => {
        this.lastPaymentClosedOrder = result.isOrderComplete;

        if (result.isOrderComplete && orderId) {
          this.printFinalTicket(orderId);
        }

        if (result.type === 'charge_session') {
          if (result.isOrderComplete) {
            this.showPaymentSuccess = true;
          } else {
            console.log('Charge session payment recorded, returning to split modal');
            this.onSplitBill();
          }
        } else if (result.type === 'partial') {
          if (result.isOrderComplete) {
            this.showPaymentSuccess = true;
          } else {
            if (result.isManual) {
              console.log('Manual partial payment complete, returning to table overview');
              this.loadSessionSummary();
            } else {
              console.log('Calling onSplitBill to return to split modal');
              this.onSplitBill();
            }
          }
        } else {
          if (result.isOrderComplete) {
            this.showPaymentSuccess = true;
          } else {
            this.loadSessionSummary();
          }
        }

        this.currentPaymentAmount = 0;
        this.fromMesas = false;
        this.isPartialPayment = false;
        this.paymentFacade.setIsProcessingPayment(false);
      },
      error: () => {
        this.paymentFacade.setIsProcessingPayment(false);
      },
    });
  }

  private printPaymentTicket(saleId: string): void {
    this.printPaymentTicketText(saleId);
  }

  private printPaymentTicketText(saleId: string): void {
    this.tpvService.getPaymentTicketText(saleId, '58').pipe(take(1)).subscribe({
      next: (text) => {
        this.lastPaymentTicketText = text;
        this.lastPaymentSaleId = saleId;
        this.openPrintWindow(text);
      },
      error: (error) => console.error('Error printing payment ticket:', error),
    });
  }

  private printFinalTicket(orderId: string): void {
    this.tpvService.printTicketOnPrinter(orderId).pipe(take(1)).subscribe({
      next: () => {
        this.lastFinalOrderId = orderId;
      },
      error: () => {
        this.printFinalTicketText(orderId);
      },
    });
  }

  private printFinalTicketText(orderId: string): void {
    this.tpvService.getFinalTicketText(orderId, '58').pipe(take(1)).subscribe({
      next: (text) => {
        this.lastFinalTicketText = text;
        this.lastFinalOrderId = orderId;
        this.openPrintWindow(text);
      },
      error: (error) => console.error('Error printing final ticket:', error),
    });
  }

  public onPrintPaymentClick(): void {
    if (this.lastPaymentTicketText) {
      this.openPrintWindow(this.lastPaymentTicketText);
      return;
    }

    if (this.lastPaymentSaleId) {
      this.printPaymentTicket(this.lastPaymentSaleId);
      return;
    }

    console.warn('No payment ticket available');
  }

  public onPrintFinalClick(): void {
    if (this.lastFinalTicketText) {
      this.openPrintWindow(this.lastFinalTicketText);
      return;
    }

    const orderId = this.lastFinalOrderId ?? this.selectedTable?.order_id ?? null;
    if (orderId) {
      this.printFinalTicket(orderId);
      return;
    }

    console.warn('No final ticket text available');
  }

  private openPrintWindow(text: string): void {
    const win = window.open('', '_blank');
    if (!win) {
      console.warn('Unable to open print window');
      return;
    }

    const escaped = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    win.document.write('<pre style="font-family: monospace; font-size: 12px;">' + escaped + '</pre>');
    win.document.close();
    win.focus();
    win.print();
  }

  public onEqualRoundFixedPartChanged(value: number | null): void {
    this.equalRoundFixedPartCents = value;
  }

  public onConfirmSplit(data: { selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean; chargeSessionId?: string }): void {
    console.log('Split confirmed:', data);
    console.log('Before - paidDiners:', this.paidDiners);

    this.lastPaymentWasEqualPart = !!data.isEqualPart;

    if (data.chargeSessionId) {
      this.paymentFacade.setCurrentChargeSession({
        id: data.chargeSessionId,
        amountPerDiner: data.amount || 0
      });
    }
    this.paymentFacade.setCurrentDinerNumber(data.diner || null);

    const selectedLines = data.selectedLines;
    const total = data.amount || selectedLines.reduce((sum, l) => sum + l.price, 0);

    if (total > 0) {
      if (data.isEqualPart) {

        this.selectedTableLines = this.selectedTableLines.map((l) => ({ ...l }));
        this.currentPaymentAmount = data.amount || total;
        console.log('Set currentPaymentAmount:', this.currentPaymentAmount);

        this.fromMesas = false;
        this.isPartialPayment = true;
      } else {

        this.selectedTableLines = selectedLines.map((l) => ({ ...l }));
        this.currentPaymentAmount = total;

        this.isPartialPayment = !!data.chargeSessionId || (this.selectedTable?.diners ?? 1) > 1;
      }
      this.showSplitModal = false;
      this.showCobrarModal = true;
    } else {
      this.showSplitModal = false;
      this.selectedTable = null;
    }
  }

  public onChargeSessionUpdated(session: any): void {
    this.paymentFacade.setLoadedChargeSession(session);
    if (this.selectedTable && session) {
      this.selectedTable.diners = session.diners_count;
      this.paidDiners = session.paid_diner_numbers || [];
    }
  }

  public onRefundLine(event: { orderLineId: string; lineName: string; price: number }): void {
    const session = this.loadedChargeSession();
    const userId = this.currentUser?.id;
    if (!session || !userId) {
      console.warn('onRefundLine: missing session or user', { session, userId });
      return;
    }

    this.chargeSessionService
      .refundLine(session.id, {
        order_line_id: event.orderLineId,
        refunded_by_user_id: userId,
        reason: `Reembolso de "${event.lineName}"`,
      })
      .subscribe({
        next: (updated) => {

          this.equalRoundFixedPartCents = null;
          this.onChargeSessionUpdated(updated);
        },
        error: (err) => {
          console.error('Error refunding line', err);
          const detail = err?.error?.message || err?.message || 'Error desconocido';
          alert(`No se pudo reembolsar la línea: ${detail}`);
        },
      });
  }

  public onPaymentSuccessComplete(): void {
    this.resetPaymentState();
    this.loadSessionSummary();
  }

  private resetPaymentState(): void {
    this.showCobrarModal = false;
    this.showSplitModal = false;
    this.showPaymentSuccess = false;
    this.selectedTable = null;
    this.selectedTableLines = [];
    this.linePrices.clear();
    this.paidDiners = [];
    this.equalRoundPaidDinerNumbers = [];
    this.equalRoundFixedPartCents = null;
    this.lastSplitOrderId = null;
    this.originalOrderTotal = 0;
    this.currentPaymentAmount = 0;
    this.fromMesas = false;
    this.isPartialPayment = false;
    this.pendingTableToCharge = null;
    this.paymentFacade.setLoadedChargeSession(null);
    this.paymentFacade.setCurrentChargeSession(null);
    this.paymentFacade.setCurrentDinerNumber(null);
    this.lastPaymentTicketText = null;
    this.lastFinalTicketText = null;
    this.lastPaymentSaleId = null;
    this.lastFinalOrderId = null;
    this.lastPaymentClosedOrder = false;
  }

  public getPerDinerAmount(total: number, diners: number): number {
    if (diners <= 0) return 0;
    return Math.floor(total / diners);
  }

  private captureLinePrices(lines: ReadonlyArray<{ id?: string; price: number }>): void {
    this.linePrices.clear();
    for (const l of lines) {
      if (l.id) this.linePrices.set(l.id, l.price);
    }
  }

  /**
   * Actualizar el total pendiente de una mesa en la lista pendingTables
   */
  private updatePendingTableTotal(orderId: string, newTotalCents: number): void {
    const currentTables = this.pendingTables;
    const updatedTables = currentTables.map((table) =>
      table.order_id === orderId ? { ...table, total: newTotalCents } : table
    );
    this.setPendingTables(updatedTables);
  }
}
