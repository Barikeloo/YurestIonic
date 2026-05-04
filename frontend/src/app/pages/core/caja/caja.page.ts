import { CommonModule } from '@angular/common';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { BehaviorSubject, forkJoin, Observable, of, Subject, throwError } from 'rxjs';
import { catchError, finalize, switchMap, take, takeUntil, map } from 'rxjs/operators';
import { AuthService } from '../../../services/auth.service';
import { PinAuthService, AuthContext, AuthActionType } from '../../../services/pin-auth.service';
import { PinAuthResult } from '../../../components/pin-auth-modal/pin-auth-modal.component';
import { TpvService, TpvCashSession, TpvCashSessionListItem, TpvOrder, TpvSale, TpvTableItem } from '../../../services/tpv.service';
import { ChargeSessionService, ChargeSession, RecordPaymentResponse } from '../../../services/charge-session.service';
import { OpenCashModalComponent } from '../../../components/open-cash-modal/open-cash-modal.component';
import { PinAuthModalComponent } from '../../../components/pin-auth-modal/pin-auth-modal.component';
import { CashMovementModalComponent } from '../../../components/cash-movement-modal/cash-movement-modal.component';
import { ClosingWizardComponent, ZReportData } from '../../../components/closing-wizard/closing-wizard.component';
import { CobrarModalComponent, OrderLine } from '../../../components/cobrar-modal/cobrar-modal.component';
import { SplitBillModalComponent, BillLine } from '../../../components/split-bill-modal/split-bill-modal.component';
import { MesasAbiertasComponent, PendingTable } from '../../../components/mesas-abiertas/mesas-abiertas.component';
import { MethodBarComponent, MethodBreakdown } from '../../../components/method-bar/method-bar.component';
import { MovimientosListComponent, CashMovement } from '../../../components/movimientos-list/movimientos-list.component';
import { CardComponent } from '../../../components/card/card.component';
import { BadgeComponent } from '../../../components/badge/badge.component';
import { BtnComponent } from '../../../components/btn/btn.component';
import { KpiCardComponent } from '../../../components/kpi-card/kpi-card.component';
import { SegmentComponent } from '../../../components/segment/segment.component';
import { PaymentSuccessComponent } from '../../../components/payment-success/payment-success.component';
import { DinersStatusComponent } from '../../../components/diners-status/diners-status.component';

type CajaState = 'pre-apertura' | 'activa' | 'arqueo' | 'historico';

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

interface OrphanSessionData {
  id: string;
  opened_by_user_id: string;
  opened_at: string;
  device_id: string;
}

interface CashSessionSummary {
  initial_amount_cents: number;
  total_sales: number;
  total_cash_payments: number;
  total_card_payments: number;
  total_bizum_payments: number;
  total_other_payments: number;
  total_in_movements: number;
  total_out_movements: number;
  expected_amount: number;
  movements_count: number;
  payments_count: number;
}

interface CashMovementItem {
  uuid: string;
  type: 'in' | 'out';
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

interface MethodBreakdownRow {
  key: string;
  label: string;
  color: string;
  amount_cents: number;
  percentage: number;
}

@Component({
  selector: 'app-caja',
  templateUrl: './caja.page.html',
  styleUrls: ['./caja.page.scss'],
  imports: [
    CommonModule,
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
    DinersStatusComponent,
  ],
  standalone: true,
})
export class CajaPage implements OnInit, OnDestroy {
  // BehaviorSubjects para estado reactivo
  public readonly state$ = new BehaviorSubject<CajaState>('pre-apertura');
  public readonly activeSession$ = new BehaviorSubject<TpvCashSession | null>(null);
  public readonly loading$ = new BehaviorSubject<boolean>(true);
  public readonly pendingTables$ = new BehaviorSubject<PendingTableRow[]>([]);
  public readonly movements$ = new BehaviorSubject<CashMovementItem[]>([]);
  public readonly sessionSummary$ = new BehaviorSubject<CashSessionSummary | null>(null);

  // Getters para compatibilidad con templates existentes
  public get state(): CajaState { return this.state$.value; }
  public get activeSession(): TpvCashSession | null { return this.activeSession$.value; }
  public get loading(): boolean { return this.loading$.value; }
  public get pendingTables(): PendingTableRow[] { return this.pendingTables$.value; }
  public get movements(): CashMovementItem[] { return this.movements$.value; }
  public get sessionSummary(): CashSessionSummary | null { return this.sessionSummary$.value; }

  // Setters para actualizar BehaviorSubjects
  private setState(value: CajaState): void { this.state$.next(value); }
  private setActiveSession(value: TpvCashSession | null): void { this.activeSession$.next(value); }
  private setLoading(value: boolean): void { this.loading$.next(value); }
  private setPendingTables(value: PendingTableRow[]): void { this.pendingTables$.next(value); }
  private setMovements(value: CashMovementItem[]): void { this.movements$.next(value); }
  private setSessionSummary(value: CashSessionSummary | null): void { this.sessionSummary$.next(value); }

  public lastClosed: LastClosedData | null = null;
  public orphanSession: OrphanSessionData | null = null;
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
  public currentUser: { id: string } | null = null;
  public fromMesas = false;
  public isPartialPayment = false;
  public paidDiners: number[] = [];
  public originalOrderTotal = 0;
  public currentPaymentAmount = 0;
  public isClosingInProgress = false;
  public isProcessingPayment = false;
  public currentChargeSession: { id: string; amountPerDiner: number } | null = null;
  public currentDinerNumber: number | null = null;
  public loadedChargeSession: ChargeSession | null = null;

  private refreshInterval: any;
  private clockInterval: any;
  private readonly destroy$ = new Subject<void>();
  // Subject para cancelar peticiones de carga de orden en curso (A7 fix)
  private readonly loadOrderDestroy$ = new Subject<void>();
  // Subject para cancelar flujos de pago previos (A7 fix)
  private readonly paymentFlowDestroy$ = new Subject<void>();
  public readonly deviceId: string;

  constructor(
    private readonly tpvService: TpvService,
    private readonly authService: AuthService,
    private readonly pinAuthService: PinAuthService,
    private readonly chargeSessionService: ChargeSessionService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {
    this.deviceId = this.authService.getDeviceId();
  }

  public ngOnInit(): void {
    this.updateClock();
    this.clockInterval = setInterval(() => this.updateClock(), 1000);
    this.authService.currentUser$.pipe(takeUntil(this.destroy$)).subscribe((user) => { this.currentUser = user; });
    this.loadActiveSession();

    // Check if coming from mesas to open payment modal
    // Clear URL params after opening to prevent modal from opening on reload
    this.route.queryParams.pipe(takeUntil(this.destroy$)).subscribe((params) => {
      if (params['orderId'] && params['fromMesas'] === 'true') {
        this.loadOrderForPayment(params['orderId']);
        // Clear URL params to prevent modal from opening on page reload
        this.router.navigate([], {
          relativeTo: this.route,
          queryParams: {},
          replaceUrl: true,
        });
      }
    });
  }

  public ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
    this.loadOrderDestroy$.next();
    this.loadOrderDestroy$.complete();
    this.paymentFlowDestroy$.next();
    this.paymentFlowDestroy$.complete();
    this.stopRefreshInterval();
    if (this.clockInterval) clearInterval(this.clockInterval);
    // Complete BehaviorSubjects to prevent memory leaks
    this.state$.complete();
    this.activeSession$.complete();
    this.loading$.complete();
    this.pendingTables$.complete();
    this.movements$.complete();
    this.sessionSummary$.complete();
  }

  private loadOrderForPayment(orderId: string): void {
    // Cancel any previous load order request (A7 fix - cancel previous subscriptions)
    this.loadOrderDestroy$.next();
    this.resetPaymentState();
    this.fromMesas = true;

    // Use switchMap chain to avoid nested subscriptions (A7 fix)
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
            // Fallback: assume no payments made
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

        // Calculate paid diners based on total paid (handle single diner case)
        const diners = order.diners ?? 1;
        const partAmount = diners > 1 ? Math.floor(originalTotal / diners) : originalTotal;
        const paidDinersCount = diners > 1 && partAmount > 0 ? Math.floor(paidTotal / partAmount) : (paidTotal > 0 ? 1 : 0);
        this.paidDiners = Array.from({ length: paidDinersCount }, (_, i) => i + 1);

        // Create a temporary selectedTable from the order with remaining total
        this.selectedTable = {
          order_id: orderId,
          table_name: tableName,
          total: remainingTotal,
          status: order.status,
          diners: diners,
          opened_at: order.opened_at || new Date().toISOString(),
        } as PendingTable;
        this.selectedTableLines = lines.map((l) => ({
          id: l.id,
          name: l.product_name || 'Producto',
          price: l.price * l.quantity,
        }));
        // Set the payment amount to the remaining total
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
    this.tpvService.getActiveCashSession(this.deviceId).pipe(takeUntil(this.destroy$)).subscribe({
      next: (session) => {
        this.setActiveSession(session);
        if (session === null) {
          this.setState('pre-apertura');
          this.loadLastClosedData();
          this.stopRefreshInterval();
        } else {
          this.setLoading(false);
          switch (session.status) {
            case 'open':
              this.setState('activa');
              this.loadSessionSummary();
              this.loadActiveDashboardData();
              this.startRefreshInterval();
              break;
            case 'closing':
              this.setState('arqueo');
              this.showWizard = true;
              this.loadSessionSummary();
              this.stopRefreshInterval();
              break;
            case 'closed':
            case 'abandoned':
              this.setState('historico');
              this.loadClosedSessions();
              this.stopRefreshInterval();
              break;
          }
        }
      },
      error: () => {
        this.setLoading(false);
        this.setState('pre-apertura');
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
    if (!this.activeSession) return;
    this.tpvService.getCashSessionSummary(this.activeSession.uuid).pipe(takeUntil(this.destroy$)).subscribe({
      next: (summary) => {
        this.setSessionSummary(summary as unknown as CashSessionSummary);
        this.loadActiveDashboardData();
      },
      error: (error) => { console.error('Error loading session summary:', error); },
    });
  }

  private loadActiveDashboardData(): void {
    if (!this.activeSession) return;
    const sessionUuid = this.activeSession.uuid;
    this.tpvService.listCashMovements(sessionUuid).pipe(takeUntil(this.destroy$)).subscribe({
      next: (response) => { this.setMovements(response.movements as CashMovementItem[]); },
      error: () => { this.setMovements([]); },
    });

    forkJoin({
      orders: this.tpvService.listOrders().pipe(catchError(() => of([] as TpvOrder[]))),
      tables: this.tpvService.listTables().pipe(catchError(() => of([] as TpvTableItem[]))),
    }).pipe(takeUntil(this.destroy$)).subscribe(({ orders, tables }) => {
      const tableNameById = new Map(tables.map((t) => [t.id, t.name] as const));
      this.setPendingTables(orders
        .filter((o) => o.status === 'open' || o.status === 'to-charge')
        .map((o) => ({
          order_id: o.id,
          table_name: tableNameById.get(o.table_id) ?? 'Mesa',
          diners: o.diners,
          opened_at: o.opened_at,
          total: o.total,
        })));
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
    // Abrir caja es acción CRÍTICA - siempre requiere PIN (pero extiende sesión para otras acciones)
    if (this.pinAuthService.requiresPin('critical')) {
      this.showPinAuthModal = true;
    } else {
      this.showOpenModal = true;
    }
  }

  public onPinAuthenticated(result: PinAuthResult): void {
    // Guardar contexto completo para sesión con timeout
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
    this.tpvService.openCashSession({
      device_id: this.deviceId,
      opened_by_user_id: data.userId,
      initial_amount_cents: data.initialAmountCents,
      notes: data.notes,
    }).pipe(takeUntil(this.destroy$)).subscribe({
      next: (session) => {
        this.showOpenModal = false;
        this.setActiveSession(session);
        this.setState('activa');
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
    type: string;
    reasonCode: string;
    amountCents: number;
    description?: string;
  }): void {
    if (!this.activeSession) return;
    this.tpvService.registerCashMovement({
      cash_session_id: this.activeSession.uuid,
      type: data.type,
      reason_code: data.reasonCode,
      amount_cents: data.amountCents,
      user_id: this.activeSession.opened_by_user_id,
      description: data.description,
    }).pipe(takeUntil(this.destroy$)).subscribe({
      next: () => {
        this.showMovementModal = false;
        this.loadSessionSummary();
      },
      error: (error) => { alert('Error al registrar el movimiento: ' + error.message); },
    });
  }

  public onStartClosing(): void {
    if (!this.activeSession) return;
    this.tpvService.startClosingCashSession({ cash_session_id: this.activeSession.uuid }).pipe(takeUntil(this.destroy$)).subscribe({
      next: (response) => {
        this.setState('arqueo');
        this.showWizard = true;
        // Actualizar el estado de la sesión activa para que onWizardClose funcione correctamente
        this.setActiveSession({ ...this.activeSession!, status: response.status as 'open' | 'closing' | 'closed' | 'abandoned' });
        this.loadSessionSummary();
        this.stopRefreshInterval();
      },
      error: (error) => { alert('Error al iniciar cierre: ' + error.message); },
    });
  }

  public onCancelClosing(): void {
    if (!this.activeSession) return;
    if (this.activeSession.status !== 'closing') {
      alert('Solo se puede cancelar el cierre cuando la sesión está en proceso de cierre.');
      return;
    }
    this.tpvService.cancelClosingCashSession({ cash_session_id: this.activeSession.uuid }).pipe(takeUntil(this.destroy$)).subscribe({
      next: (response) => {
        this.setState('activa');
        this.showWizard = false;
        // Actualizar el estado de la sesión activa
        this.setActiveSession({ ...this.activeSession!, status: response.status as 'open' | 'closing' | 'closed' | 'abandoned' });
        this.startRefreshInterval();
      },
      error: (error) => { alert('Error al cancelar el cierre: ' + error.message); },
    });
  }

  public onWizardClose(): void {
    this.showWizard = false;
    // Don't cancel if closing is already in progress
    if (this.isClosingInProgress) {
      console.log('Closing is in progress, skipping cancel');
      return;
    }
    if (this.state === 'arqueo' && this.activeSession?.status === 'closing') {
      this.tpvService.cancelClosingCashSession({ cash_session_id: this.activeSession.uuid }).pipe(takeUntil(this.destroy$)).subscribe({
        next: (response) => {
          this.setState('activa');
          // Actualizar el estado de la sesión activa
          this.setActiveSession({ ...this.activeSession!, status: response.status as 'open' | 'closing' | 'closed' | 'abandoned' });
          this.startRefreshInterval();
        },
        error: (error) => {
          console.error('Error al cancelar el cierre:', error);
          this.setState('activa');
          this.startRefreshInterval();
        },
      });
    } else if (this.state === 'arqueo') {
      this.setState('activa');
      this.startRefreshInterval();
    }
  }

  public onCompleteClosing(data: { countedAmount: number; discrepancyReason?: string }): void {
    if (!this.activeSession) return;

    // Verify session is in 'closing' status before attempting to close
    if (this.activeSession.status !== 'closing') {
      console.warn('Session is not in closing status, reloading active session...');
      this.tpvService.getActiveCashSession(this.deviceId).pipe(takeUntil(this.destroy$)).subscribe({
        next: (session) => {
          if (session && session.status === 'closing') {
            this.setActiveSession(session);
            this.proceedWithClose(data);
          } else {
            alert('Error: La sesión no está en estado de cierre. Por favor, intente iniciar el cierre nuevamente.');
            if (session) {
              this.setActiveSession(session);
              this.setState(session.status === 'open' ? 'activa' : 'arqueo');
            }
          }
        },
        error: () => {
          alert('Error al verificar el estado de la caja.');
        },
      });
      return;
    }

    this.proceedWithClose(data);
  }

  private proceedWithClose(data: { countedAmount: number; discrepancyReason?: string }): void {
    if (!this.activeSession) return;
    this.isClosingInProgress = true;
    this.tpvService.closeCashSession({
      cash_session_id: this.activeSession.uuid,
      closed_by_user_id: this.activeSession.opened_by_user_id,
      final_amount_cents: data.countedAmount,
      discrepancy_reason: data.discrepancyReason,
    }).pipe(takeUntil(this.destroy$)).subscribe({
      next: () => {
        this.isClosingInProgress = false;
        this.showWizard = false;
        this.setActiveSession(null);
        this.setState('historico');
        this.loadClosedSessions();
      },
      error: (error) => {
        this.isClosingInProgress = false;
        alert('Error al cerrar la caja: ' + error.message);
      },
    });
  }

  public get wizardExpectedAmount(): number {
    return this.sessionSummary?.expected_amount ?? 0;
  }

  public get wizardZData(): ZReportData | null {
    if (!this.sessionSummary) return null;
    return {
      tickets: this.sessionSummary.payments_count,
      diners: 0,
      gross: this.sessionSummary.total_sales,
      discounts: 0,
      net: this.sessionSummary.total_sales,
      cash: this.sessionSummary.total_cash_payments,
      card: this.sessionSummary.total_card_payments,
      bizum: this.sessionSummary.total_bizum_payments,
      invitation: this.sessionSummary.total_other_payments,
      invitations: 0,
      invValue: 0,
      cancellations: 0,
      tipsCard: 0,
      initial: this.sessionSummary.initial_amount_cents,
      movIn: this.sessionSummary.total_in_movements,
      movOut: this.sessionSummary.total_out_movements,
    };
  }

  private loadLastClosedData(): void {
    this.tpvService.getLastClosedCashSession().pipe(takeUntil(this.destroy$)).subscribe({
      next: (data) => {
        this.lastClosed = data.last_closed;
        this.orphanSession = data.orphan_session;
        this.setLoading(false);
      },
      error: () => { this.setLoading(false); },
    });
  }

  private loadClosedSessions(): void {
    this.tpvService.listCashSessions().pipe(takeUntil(this.destroy$)).subscribe({
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

  // Getters for new components
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
    if (!this.sessionSummary) return {};
    return {
      cash: this.sessionSummary.total_cash_payments || 0,
      card: this.sessionSummary.total_card_payments || 0,
      bizum: this.sessionSummary.total_bizum_payments || 0,
      transfer: this.sessionSummary.total_other_payments || 0,
    };
  }

  // Modal handlers for charging tables
  public onCobrarMesa(mesa: PendingTable): void {
    this.pendingTableToCharge = mesa;

    // Cobrar mesa es acción NORMAL - usa sesión con timeout
    if (this.pinAuthService.requiresPin('normal')) {
      this.showPinAuthModalForCobrarMesa = true;
    } else {
      this.loadOrderForCobrarMesa();
    }
  }

  private loadOrderForCobrarMesa(): void {
    const mesa = this.pendingTableToCharge;
    if (!mesa) return;

    // Cancel any previous payment flow (A7 fix)
    this.paymentFlowDestroy$.next();
    this.resetPaymentState();

    this.selectedTable = mesa;
    this.isPartialPayment = false; // Reset to false for direct payment from caja page

    // Use switchMap chain to avoid nested subscriptions (A7 fix)
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
            // Fallback to original total and no paid diners
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

        // Update selected table total to remaining amount
        if (this.selectedTable) {
          this.selectedTable.total = remainingTotal;
        }

        console.log('Order total:', originalTotal, 'Paid:', paidTotal, 'Remaining:', remainingTotal);
        console.log('Calculated paidDiners:', this.paidDiners);
        console.log('Loading order lines for order_id:', mesa.order_id);

        return this.tpvService.getOrderLines(mesa.order_id).pipe(
          takeUntil(this.paymentFlowDestroy$),
          map((lines) => ({
            lines: lines.map((l) => ({
              id: l.id,
              name: l.product_name || 'Producto',
              price: l.price * l.quantity,
            })),
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
        alert('No se pudo cargar la orden.');
        this.pendingTableToCharge = null;
        return throwError(() => error);
      })
    ).subscribe({
      next: ({ lines, remainingTotal }) => {
        this.selectedTableLines = lines;
        // Set currentPaymentAmount to remaining total for direct payment
        this.currentPaymentAmount = remainingTotal;
        this.showCobrarModal = true;
        this.pendingTableToCharge = null; // Clear pending after loading
      },
      error: () => {
        // Error already handled in catchError
      },
    });
  }

  public onPinAuthenticatedForCobrarMesa(result: PinAuthResult): void {
    // Guardar contexto completo para sesión con timeout
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
    // Cancel any previous payment flow (A7 fix)
    this.paymentFlowDestroy$.next();
    this.resetPaymentState();
    // Always recalculate paidDiners based on actual paid total
    this.selectedTable = mesa;

    // Use switchMap chain to avoid nested subscriptions (A7 fix)
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

        // Ya no calculamos paidDiners en base al total pagado para evitar suposiciones erróneas
        this.paidDiners = [];

        // Update selected table total to remaining amount
        if (this.selectedTable) {
          this.selectedTable.total = remainingTotal;
        }

        console.log('Order total:', originalTotal, 'Paid:', paidTotal, 'Remaining:', remainingTotal);
        console.log('Calculated paidDiners:', this.paidDiners);
        console.log('Loading order lines for order_id:', mesa.order_id);

        return this.tpvService.getOrderLines(mesa.order_id).pipe(
          takeUntil(this.paymentFlowDestroy$),
          map((lines) => ({
            lines: lines.map((l) => ({
              id: l.id,
              name: l.product_name || 'Producto',
              price: l.price * l.quantity,
              diner: l.diner_number,
            })),
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
        this.showSplitModal = true;
      },
      error: () => {
        // Error already handled in catchError
      },
    });
  }

  public onSplitBill(): void {
    this.showCobrarModal = false;

    if (!this.selectedTable) {
      this.showSplitModal = true;
      return;
    }

    const orderId = this.selectedTable.order_id;

    // take(1) garantiza que cada observable emite exactamente un valor y completa.
    // finalize actúa como red de seguridad: abre el modal aunque algo falle.
    forkJoin({
      orderTotal: this.tpvService.getOrderTotal(orderId).pipe(
        take(1),
        catchError(() => of({ total_cents: this.originalOrderTotal || 0 }))
      ),
      paidTotal: this.tpvService.getOrderPaidTotal(orderId).pipe(
        take(1),
        catchError(() => of({ total_cents: 0 }))
      ),
      chargeSession: this.chargeSessionService.getCurrentChargeSession(orderId).pipe(
        take(1),
        catchError((error) => {
          if (error.status === 404) return of(null);
          console.error('Error fetching charge session in onSplitBill:', error);
          return of(null);
        })
      ),
    }).pipe(
      takeUntil(this.destroy$),
      finalize(() => {
        // Red de seguridad: si el observable completa sin next (edge case),
        // abrimos el modal igualmente para no dejar al usuario bloqueado.
        if (!this.showSplitModal) {
          console.warn('onSplitBill finalize fallback: forcing showSplitModal = true');
          this.showSplitModal = true;
        }
      })
    ).subscribe({
      next: ({ orderTotal, paidTotal, chargeSession }) => {
        const paidTotalCents = paidTotal.total_cents;
        const originalTotal = orderTotal.total_cents;

        // Update originalOrderTotal to the true total from backend
        this.originalOrderTotal = originalTotal;

        this.loadedChargeSession = chargeSession;
        console.log('onSplitBill - chargeSession loaded:', chargeSession);

        if (chargeSession) {
          // La charge session es la fuente de verdad
          this.paidDiners = chargeSession.paid_diner_numbers;
          console.log('onSplitBill - synced paidDiners:', this.paidDiners, 'remaining_cents:', chargeSession.remaining_cents);
          if (this.selectedTable) {
            this.selectedTable.total = chargeSession.remaining_cents;
            this.updatePendingTableTotal(this.selectedTable.order_id, chargeSession.remaining_cents);
          }
        } else {
          // Fallback: no asumimos cuántos han pagado, dejamos que el camarero lo marque manualmente
          this.paidDiners = [];
          if (this.selectedTable) {
            const remaining = Math.max(0, originalTotal - paidTotalCents);
            this.selectedTable.total = remaining;
            this.updatePendingTableTotal(this.selectedTable.order_id, remaining);
          }
        }

        // Abrir modal DESPUÉS de actualizar todos los datos
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
    // Prevent double submission
    if (this.isProcessingPayment) {
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
      alert('Error: No hay mesa seleccionada');
      return;
    }

    if (!this.currentUser) {
      console.error('No current user');
      alert('Error: No hay usuario actual');
      return;
    }

    if (!this.selectedTable?.order_id) {
      console.error('No order_id in selected table');
      alert('Error: No hay orden seleccionada');
      return;
    }

    this.isProcessingPayment = true;

    const payments = [
      {
        method: data.method,
        amount_cents: data.amount,
        metadata: data.tip ? { tip_cents: data.tip } : undefined,
      },
    ];

    // For partial payments, check if this payment will complete the order
    // Calculate based on number of diners, not selectedTable.total
    // selectedTable.total is set to the payment amount in split modal, not the remaining amount
    let willBeComplete = false;
    if (this.isPartialPayment && this.selectedTable && !data.isManualPartial) {
      const diners = this.selectedTable.diners ?? 1;
      const paidDinersCount = this.paidDiners.length;
      // If this is the last unpaid diner (or single diner), the payment will complete the order
      willBeComplete = diners === 1 || paidDinersCount === diners - 1;
      console.log('Partial payment - Diners:', diners, 'Paid diners:', paidDinersCount, 'Will be complete:', willBeComplete);
    } else {
      const currentPaid = this.originalOrderTotal - (this.selectedTable?.total || 0);
      willBeComplete = (currentPaid + data.amount) >= this.originalOrderTotal;
    }

    // For complete payments, send undefined order_line_ids so backend treats it as full payment
    // For partial/split payments, send the specific line IDs
    // If it's a manual partial payment, we don't assign specific lines, so it's undefined
    const orderLineIds = (willBeComplete || data.isManualPartial)
      ? undefined
      : this.selectedTableLines
          .filter((l) => l.id)
          .map((l) => l.id) as string[];

    console.log('Order line IDs:', orderLineIds);

    console.log('Payment calculation - This payment:', data.amount, 'Original total:', this.originalOrderTotal, 'Will be complete:', willBeComplete);

    // When from mesas, calculate if payment is complete based on amount vs remaining total
    // Only send is_partial_payment: true if the payment is actually partial
    let isPartialPayment = this.isPartialPayment || !!data.isManualPartial;
    if (this.fromMesas || data.isManualPartial) {
      // If this payment completes the order, send is_partial_payment: false
      // Otherwise send is_partial_payment: true
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

    // Cancel any previous payment flow (A7 fix)
    this.paymentFlowDestroy$.next();

    const orderId = this.selectedTable?.order_id;
    const activeSessionId = this.loadedChargeSession?.status === 'active'
      ? this.loadedChargeSession.id
      : null;

    const paymentMethod = payments[0].method;
    const mappedMethod: 'cash' | 'card' | 'bizum' | 'voucher' | 'invitation' | 'other' =
      (['cash', 'card', 'bizum', 'voucher', 'invitation'] as const).includes(paymentMethod as any)
        ? (paymentMethod as 'cash' | 'card' | 'bizum' | 'voucher' | 'invitation')
        : 'other';

    // Cuando hay sesión activa, todo cobro (libre o split) se canaliza por
    // recordPayment, que crea la Sale internamente y gestiona el ciclo de
    // vida de la sesión (cierre por deuda 0). Llamar a tpvService.createSale
    // por separado generaría una Sale duplicada y descuadraría paid_cents.
    const sale$: Observable<RecordPaymentResponse | TpvSale> = activeSessionId
      ? this.chargeSessionService.recordPayment(activeSessionId, {
          payment_method: mappedMethod,
          amount_cents: data.amount,
          diner_number: this.currentDinerNumber ?? undefined,
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

    sale$.pipe(
      takeUntil(this.destroy$),
      takeUntil(this.paymentFlowDestroy$),
      switchMap((result) => {
        console.log('Payment registered:', result);
        this.showCobrarModal = false;

        if (activeSessionId) {
          const paymentResponse = result as RecordPaymentResponse;
          return this.chargeSessionService.getCurrentChargeSession(orderId!).pipe(
            takeUntil(this.paymentFlowDestroy$),
            map((freshSession) => {
              console.log('Charge session reloaded after payment:', freshSession);
              this.loadedChargeSession = freshSession;
              this.paidDiners = freshSession.paid_diner_numbers;
              if (this.selectedTable) {
                this.selectedTable.total = freshSession.remaining_cents;
                this.updatePendingTableTotal(this.selectedTable.order_id, freshSession.remaining_cents);
              }
              this.currentChargeSession = null;
              this.currentDinerNumber = null;
              return { type: 'charge_session' as const, isOrderComplete: paymentResponse.is_session_complete };
            }),
            catchError((error) => {
              console.warn('Could not reload charge session after payment:', error);
              this.currentChargeSession = null;
              this.currentDinerNumber = null;
              return of({
                type: 'charge_session' as const,
                isOrderComplete: paymentResponse.is_session_complete,
              });
            })
          );
        }

        // For partial payments, get paid total to check if order is complete
        if (isPartialPayment && orderId) {
          return this.tpvService.getOrderPaidTotal(orderId).pipe(
            takeUntil(this.paymentFlowDestroy$),
            map((paidResponse) => {
              const paidTotal = paidResponse.total_cents;
              const originalTotal = this.originalOrderTotal || this.selectedTable?.total || 0;
              
              // Ya no calculamos paidDiners en base al total pagado
              this.paidDiners = [];
              console.log('Reset paidDiners for manual partial payment');

              const isOrderComplete = paidTotal >= originalTotal;
              
              // Record if this was a manual partial payment
              const isManual = !!data.isManualPartial;
              
              return { type: 'partial' as const, isOrderComplete, orderId, isManual };
            }),
            catchError((error) => {
              console.error('Error fetching paid total for diner calculation:', error);
              return of({ type: 'partial' as const, isOrderComplete: false, orderId, isManual: !!data.isManualPartial });
            })
          );
        }

        // For non-partial payments, verify with fresh GET from backend
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
              // Fallback to willBeComplete on error
              return of({ type: 'full' as const, isOrderComplete: willBeComplete });
            })
          );
        }

        // Fallback if no orderId
        return of({ type: 'full' as const, isOrderComplete: willBeComplete });
      }),
      catchError((error) => {
        console.error('Error creating sale:', error);
        alert('Error al crear la venta: ' + (error.message || 'Error desconocido'));
        this.isProcessingPayment = false;
        return throwError(() => error);
      })
    ).subscribe({
      next: (result) => {
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
        this.isProcessingPayment = false;
      },
      error: () => {
        // Error already handled in catchError
        this.isProcessingPayment = false;
      },
    });
  }

  public onConfirmSplit(data: { selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean; chargeSessionId?: string }): void {
    console.log('Split confirmed:', data);
    console.log('Before - paidDiners:', this.paidDiners);

    // Guardar sesión y comensal para registro posterior
    if (data.chargeSessionId) {
      this.currentChargeSession = {
        id: data.chargeSessionId,
        amountPerDiner: data.amount || 0
      };
    }
    this.currentDinerNumber = data.diner || null;

    const selectedLines = data.selectedLines;
    const total = data.amount || selectedLines.reduce((sum, l) => sum + l.price, 0);

    if (total > 0) {
      // For equal parts, use all lines for the first part, adjust total
      if (data.isEqualPart) {
        this.selectedTableLines = this.selectedTableLines.map((l) => ({
          id: l.id,
          name: l.name,
          price: l.price,
        }));
        // Set the current payment amount to the part amount
        this.currentPaymentAmount = data.amount || total;
        console.log('Set currentPaymentAmount:', this.currentPaymentAmount);
        // Adjust the selected table total to the part amount
        if (this.selectedTable) {
          this.selectedTable.total = data.amount || total;
        }
        // Set flags to indicate this is a partial payment
        this.fromMesas = false;
        this.isPartialPayment = true;
        // Track paid diner — NO lo añadimos aquí; lo sincronizamos desde el
        // backend después de registrar el pago en onConfirmPayment.
        // Añadirlo antes provoca desincronización con el cálculo del último comensal.
      } else {
        // For line-based split, use selected lines
        this.selectedTableLines = selectedLines.map((l) => ({
          id: l.id,
          name: l.name,
          price: l.price,
        }));
        this.currentPaymentAmount = total;
        this.isPartialPayment = false;
      }
      this.showSplitModal = false;
      this.showCobrarModal = true;
    } else {
      this.showSplitModal = false;
      this.selectedTable = null;
    }
  }

  public onChargeSessionUpdated(session: any): void {
    this.loadedChargeSession = session;
    if (this.selectedTable && session) {
      this.selectedTable.diners = session.diners_count;
      // Actualizar paidDiners de la sesión
      this.paidDiners = session.paid_diner_numbers || [];
    }
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
    this.paidDiners = [];
    this.originalOrderTotal = 0;
    this.currentPaymentAmount = 0;
    this.fromMesas = false;
    this.isPartialPayment = false;
    this.pendingTableToCharge = null;
    this.loadedChargeSession = null;
    this.currentChargeSession = null;
    this.currentDinerNumber = null;
  }

  public getPerDinerAmount(total: number, diners: number): number {
    if (diners <= 0) return 0;
    return Math.floor(total / diners);
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
