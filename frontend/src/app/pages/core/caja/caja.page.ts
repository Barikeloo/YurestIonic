import { CommonModule } from '@angular/common';
import { Component, OnInit, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { AuthService } from '../../../services/auth.service';
import { TpvService, TpvCashSession, TpvCashSessionListItem, TpvOrder, TpvTableItem } from '../../../services/tpv.service';
import { OpenCashModalComponent } from '../../../components/open-cash-modal/open-cash-modal.component';
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

type CajaState = 'pre-apertura' | 'activa' | 'arqueo' | 'historico';

interface LastClosedData {
  id: string;
  opened_by_user_id: string;
  closed_by_user_id: string | null;
  closed_at: string | null;
  final_amount_cents: number | null;
  discrepancy_cents: number | null;
  discrepancy_reason: string | null;
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
    SegmentComponent,
  ],
  standalone: true,
})
export class CajaPage implements OnInit, OnDestroy {
  public state: CajaState = 'pre-apertura';
  public activeSession: TpvCashSession | null = null;
  public loading = true;
  public lastClosed: LastClosedData | null = null;
  public orphanSession: OrphanSessionData | null = null;
  public showOpenModal = false;
  public availableUsers: Array<{ id: string; name: string; initials: string }> = [];
  public sessionSummary: CashSessionSummary | null = null;
  public showMovementModal = false;
  public showWizard = false;
  public currentTime = '';
  public closedSessions: TpvCashSessionListItem[] = [];
  public openCashError: string | null = null;
  public movements: CashMovementItem[] = [];
  public pendingTables: PendingTableRow[] = [];
  public showCobrarModal = false;
  public showSplitModal = false;
  public selectedTable: PendingTable | null = null;
  public selectedTableLines: OrderLine[] = [];
  public currentUser: { id: string } | null = null;
  public fromMesas = false;
  public isPartialPayment = false;
  public paidDiners: number[] = [];
  public originalOrderTotal = 0;
  public currentPaymentAmount = 0;

  private refreshInterval: any;
  private clockInterval: any;
  public readonly deviceId: string;

  constructor(
    private readonly tpvService: TpvService,
    private readonly authService: AuthService,
    private readonly route: ActivatedRoute,
  ) {
    this.deviceId = this.authService.getDeviceId();
  }

  public ngOnInit(): void {
    this.updateClock();
    this.clockInterval = setInterval(() => this.updateClock(), 1000);
    this.authService.currentUser$.subscribe((user) => { this.currentUser = user; });
    this.loadActiveSession();

    // Check if coming from mesas to open payment modal
    this.route.queryParams.subscribe((params) => {
      if (params['orderId'] && params['fromMesas'] === 'true') {
        this.loadOrderForPayment(params['orderId']);
      }
    });
  }

  public ngOnDestroy(): void {
    this.stopRefreshInterval();
    if (this.clockInterval) clearInterval(this.clockInterval);
  }

  private loadOrderForPayment(orderId: string): void {
    this.fromMesas = true;
    this.paidDiners = [];
    this.tpvService.getOrder(orderId).subscribe({
      next: (order) => {
        // Load tables to get the table name
        this.tpvService.listTables().subscribe({
          next: (tables) => {
            const table = tables.find((t) => t.id === order.table_id);
            const tableName = table?.name || order.table_id || 'Mesa';

            this.tpvService.getOrderLines(orderId).subscribe({
              next: (lines) => {
                const originalTotal = lines.reduce((sum, l) => sum + l.price * l.quantity, 0);
                this.originalOrderTotal = originalTotal;

                // Get paid total to calculate remaining amount
                this.tpvService.getOrderPaidTotal(orderId).subscribe({
                  next: (paidResponse) => {
                    const paidTotal = paidResponse.total_cents;
                    const remainingTotal = Math.max(0, originalTotal - paidTotal);

                    // Create a temporary selectedTable from the order with remaining total
                    this.selectedTable = {
                      order_id: orderId,
                      table_name: tableName,
                      total: remainingTotal,
                      status: order.status,
                      diners: order.diners || 1,
                      opened_at: order.opened_at || new Date().toISOString(),
                    } as PendingTable;
                    this.selectedTableLines = lines.map((l) => ({
                      id: l.id,
                      name: l.product_name || 'Producto',
                      price: l.price * l.quantity,
                    }));
                    console.log('loadOrderForPayment - Original:', originalTotal, 'Paid:', paidTotal, 'Remaining:', remainingTotal);
                    this.showCobrarModal = true;
                  },
                  error: (error) => {
                    console.error('Error fetching paid total:', error);
                    // Fallback to original total
                    this.selectedTable = {
                      order_id: orderId,
                      table_name: tableName,
                      total: originalTotal,
                      status: order.status,
                      diners: order.diners || 1,
                      opened_at: order.opened_at || new Date().toISOString(),
                    } as PendingTable;
                    this.selectedTableLines = lines.map((l) => ({
                      id: l.id,
                      name: l.product_name || 'Producto',
                      price: l.price * l.quantity,
                    }));
                    this.showCobrarModal = true;
                  },
                });
              },
              error: (error) => {
                console.error('Error loading order lines:', error);
                this.selectedTableLines = [];
                this.showCobrarModal = true;
              },
            });
          },
          error: (error) => {
            console.error('Error loading tables:', error);
            this.selectedTableLines = [];
            this.showCobrarModal = true;
          },
        });
      },
      error: (error) => {
        console.error('Error loading order:', error);
        this.selectedTableLines = [];
        this.showCobrarModal = true;
      },
    });
  }

  private loadActiveSession(): void {
    this.tpvService.getActiveCashSession(this.deviceId).subscribe({
      next: (session) => {
        this.activeSession = session;
        if (session === null) {
          this.state = 'pre-apertura';
          this.loadLastClosedData();
          this.stopRefreshInterval();
        } else {
          this.loading = false;
          switch (session.status) {
            case 'open':
              this.state = 'activa';
              this.loadSessionSummary();
              this.loadActiveDashboardData();
              this.startRefreshInterval();
              break;
            case 'closing':
              this.state = 'arqueo';
              this.showWizard = true;
              this.loadSessionSummary();
              this.stopRefreshInterval();
              break;
            case 'closed':
            case 'abandoned':
              this.state = 'historico';
              this.loadClosedSessions();
              this.stopRefreshInterval();
              break;
          }
        }
      },
      error: () => {
        this.loading = false;
        this.state = 'pre-apertura';
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
    this.tpvService.getCashSessionSummary(this.activeSession.uuid).subscribe({
      next: (summary) => {
        this.sessionSummary = summary as unknown as CashSessionSummary;
        this.loadActiveDashboardData();
      },
      error: (error) => { console.error('Error loading session summary:', error); },
    });
  }

  private loadActiveDashboardData(): void {
    if (!this.activeSession) return;
    const sessionUuid = this.activeSession.uuid;
    this.tpvService.listCashMovements(sessionUuid).subscribe({
      next: (response) => { this.movements = response.movements as CashMovementItem[]; },
      error: () => { this.movements = []; },
    });

    forkJoin({
      orders: this.tpvService.listOrders().pipe(catchError(() => of([] as TpvOrder[]))),
      tables: this.tpvService.listTables().pipe(catchError(() => of([] as TpvTableItem[]))),
    }).subscribe(({ orders, tables }) => {
      const tableNameById = new Map(tables.map((t) => [t.id, t.name] as const));
      this.pendingTables = orders
        .filter((o) => o.status === 'open' || o.status === 'to-charge')
        .map((o) => ({
          order_id: o.id,
          table_name: tableNameById.get(o.table_id) ?? 'Mesa',
          diners: o.diners,
          opened_at: o.opened_at,
          total: o.total,
        }));
    });
  }

  private startRefreshInterval(): void {
    this.stopRefreshInterval();
    this.refreshInterval = setInterval(() => this.loadSessionSummary(), 30000);
  }

  private stopRefreshInterval(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
      this.refreshInterval = null;
    }
  }

  public onOpenModal(): void {
    this.loadAvailableUsers();
    this.showOpenModal = true;
  }

  private loadAvailableUsers(): void {
    const restaurantUuid = this.authService.currentUserSnapshot?.restaurantId;
    this.tpvService.listUsers(this.deviceId, restaurantUuid).subscribe({
      next: (response) => {
        this.availableUsers = response.users.map((u: any) => ({
          id: u.user_uuid,
          name: u.name,
          initials: u.name
            .split(' ')
            .slice(0, 2)
            .map((w: string) => w[0].toUpperCase())
            .join(''),
        }));
      },
      error: (error) => { console.error('Error loading users:', error); },
    });
  }

  public onOpenCash(data: { userId: string; initialAmountCents: number; notes?: string }): void {
    this.openCashError = null;
    this.tpvService.openCashSession({
      device_id: this.deviceId,
      opened_by_user_id: data.userId,
      initial_amount_cents: data.initialAmountCents,
      notes: data.notes,
    }).subscribe({
      next: (session) => {
        this.showOpenModal = false;
        this.activeSession = session;
        this.state = 'activa';
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
    }).subscribe({
      next: () => {
        this.showMovementModal = false;
        this.loadSessionSummary();
      },
      error: (error) => { alert('Error al registrar el movimiento: ' + error.message); },
    });
  }

  public onStartClosing(): void {
    if (!this.activeSession) return;
    this.tpvService.startClosingCashSession({ cash_session_id: this.activeSession.uuid }).subscribe({
      next: () => {
        this.state = 'arqueo';
        this.showWizard = true;
        this.loadSessionSummary();
        this.stopRefreshInterval();
      },
      error: (error) => { alert('Error al iniciar el cierre: ' + error.message); },
    });
  }

  public onCancelClosing(): void {
    if (!this.activeSession) return;
    this.tpvService.cancelClosingCashSession({ cash_session_id: this.activeSession.uuid }).subscribe({
      next: () => {
        this.state = 'activa';
        this.showWizard = false;
        this.startRefreshInterval();
      },
      error: (error) => { alert('Error al cancelar el cierre: ' + error.message); },
    });
  }

  public onCompleteClosing(data: { countedAmount: number; discrepancyReason?: string }): void {
    if (!this.activeSession) return;
    this.tpvService.closeCashSession({
      cash_session_id: this.activeSession.uuid,
      closed_by_user_id: this.activeSession.opened_by_user_id,
      final_amount_cents: data.countedAmount,
      discrepancy_reason: data.discrepancyReason,
    }).subscribe({
      next: () => {
        this.showWizard = false;
        this.activeSession = null;
        this.state = 'historico';
        this.loadClosedSessions();
      },
      error: (error) => { alert('Error al cerrar la caja: ' + error.message); },
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
    this.tpvService.getLastClosedCashSession().subscribe({
      next: (data) => {
        this.lastClosed = data.last_closed;
        this.orphanSession = data.orphan_session;
        this.loading = false;
      },
      error: () => { this.loading = false; },
    });
  }

  private loadClosedSessions(): void {
    this.tpvService.listCashSessions().subscribe({
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
    console.log('onCobrarMesa called with mesa:', mesa);
    // Only reset paidDiners if it's a different order
    if (this.selectedTable?.order_id !== mesa.order_id) {
      this.paidDiners = [];
      this.originalOrderTotal = mesa.total;
    }
    this.selectedTable = mesa;
    this.fromMesas = false;
    console.log('Loading order for order_id:', mesa.order_id);
    this.tpvService.getOrder(mesa.order_id).subscribe({
      next: (order) => {
        console.log('Order loaded:', order);
        // Calculate remaining total
        this.tpvService.getOrderPaidTotal(mesa.order_id).subscribe({
          next: (paidResponse) => {
            const paidTotal = paidResponse.total_cents;
            const originalTotal = order.total || mesa.total;
            const remainingTotal = Math.max(0, originalTotal - paidTotal);
            // Update selected table total to remaining amount
            if (this.selectedTable) {
              this.selectedTable.total = remainingTotal;
            }
            console.log('Order total:', originalTotal, 'Paid:', paidTotal, 'Remaining:', remainingTotal);
            console.log('Loading order lines for order_id:', mesa.order_id);
            this.tpvService.getOrderLines(mesa.order_id).subscribe({
              next: (lines) => {
                console.log('Order lines loaded:', lines);
                this.selectedTableLines = lines.map((l) => ({
                  id: l.id,
                  name: l.product_name || 'Producto',
                  price: l.price * l.quantity,
                }));
                this.showCobrarModal = true;
              },
              error: (error) => {
                console.error('Error loading order lines:', error);
                this.selectedTableLines = [];
                this.showCobrarModal = true;
              },
            });
          },
          error: (error) => {
            console.error('Error fetching paid total:', error);
            // Fallback to original total
            console.log('Loading order lines for order_id:', mesa.order_id);
            this.tpvService.getOrderLines(mesa.order_id).subscribe({
              next: (lines) => {
                console.log('Order lines loaded:', lines);
                this.selectedTableLines = lines.map((l) => ({
                  id: l.id,
                  name: l.product_name || 'Producto',
                  price: l.price * l.quantity,
                }));
                this.showCobrarModal = true;
              },
              error: (error) => {
                console.error('Error loading order lines:', error);
                this.selectedTableLines = [];
                this.showCobrarModal = true;
              },
            });
          },
        });
      },
      error: (error) => {
        console.error('Error loading order:', error);
        this.selectedTableLines = [];
        this.showCobrarModal = true;
      },
    });
  }

  public onSplitMesa(mesa: PendingTable): void {
    // Only reset paidDiners if it's a different order
    if (this.selectedTable?.order_id !== mesa.order_id) {
      this.paidDiners = [];
      this.originalOrderTotal = mesa.total;
    }
    this.selectedTable = mesa;
    this.tpvService.getOrderLines(mesa.order_id).subscribe({
      next: (lines) => {
        this.selectedTableLines = lines.map((l) => ({
          id: l.id,
          name: l.product_name || 'Producto',
          price: l.price * l.quantity,
          diner: l.diner_number,
        }));
        this.showSplitModal = true;
      },
      error: (error) => {
        console.error('Error loading order lines:', error);
        this.selectedTableLines = [];
        this.showSplitModal = true;
      },
    });
  }

  public onSplitBill(): void {
    this.showCobrarModal = false;
    // Fetch paid total to calculate remaining amount
    if (this.selectedTable) {
      const orderId = this.selectedTable.order_id;
      this.tpvService.getOrderPaidTotal(orderId).subscribe({
        next: (response) => {
          const paidTotal = response.total_cents;
          // Use originalOrderTotal to calculate remaining amount
          const originalTotal = this.originalOrderTotal || this.selectedTable?.total || 0;
          const remainingTotal = Math.max(0, originalTotal - paidTotal);
          // Update the selected table total to remaining amount for split modal
          if (this.selectedTable) {
            this.selectedTable.total = remainingTotal;
          }
          this.showSplitModal = true;
        },
        error: (error) => {
          console.error('Error fetching paid total:', error);
          this.showSplitModal = true;
        },
      });
    } else {
      this.showSplitModal = true;
    }
  }

  public onConfirmPayment(data: { method: string; amount: number; tip?: number }): void {
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
      alert('Error: No hay usuario identificado');
      return;
    }

    // Only send order_line_ids if NOT from mesas (for split bill)
    // When from mesas, treat as full payment (no order_line_ids)
    const orderLineIds = this.fromMesas
      ? undefined
      : this.selectedTableLines
          .filter((l) => l.id)
          .map((l) => l.id) as string[];

    console.log('Order line IDs:', orderLineIds);

    const payments = [
      {
        method: data.method,
        amount_cents: data.amount,
        metadata: data.tip ? { tip_cents: data.tip } : undefined,
      },
    ];

    // Check if this payment will complete the order
    const currentPaid = this.originalOrderTotal - (this.selectedTable?.total || 0);
    const willBeComplete = (currentPaid + data.amount) >= this.originalOrderTotal;

    console.log('Payment calculation - Current paid:', currentPaid, 'This payment:', data.amount, 'Original total:', this.originalOrderTotal, 'Will be complete:', willBeComplete);

    // When from mesas, always send is_partial_payment: true to allow backend to use payment amount as sale total
    // Backend will close the order when total paid reaches original total
    const isPartialPayment = this.fromMesas ? true : this.isPartialPayment;

    console.log('Creating sale with payload:', {
      order_id: this.selectedTable.order_id,
      opened_by_user_id: this.currentUser.id,
      closed_by_user_id: this.currentUser.id,
      device_id: this.deviceId,
      payments,
      order_line_ids: orderLineIds,
      is_partial_payment: isPartialPayment,
    });

    this.tpvService.createSale({
      order_id: this.selectedTable.order_id,
      opened_by_user_id: this.currentUser.id,
      closed_by_user_id: this.currentUser.id,
      device_id: this.deviceId,
      payments,
      order_line_ids: orderLineIds,
      is_partial_payment: isPartialPayment,
    }).subscribe({
      next: (sale) => {
        console.log('Sale created successfully:', sale);
        this.showCobrarModal = false;

        // If this payment completed the order, close it
        if (willBeComplete) {
          this.selectedTable = null;
          this.paidDiners = [];
          this.originalOrderTotal = 0;
        }

        this.currentPaymentAmount = 0;
        this.fromMesas = false;
        this.isPartialPayment = false;
        this.loadActiveDashboardData();
      },
      error: (error) => {
        console.error('Error creating sale:', error);
        alert('Error al crear la venta: ' + (error.message || 'Error desconocido'));
      },
    });
  }

  public onConfirmSplit(data: { selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean }): void {
    console.log('Split confirmed:', data);
    console.log('Before - paidDiners:', this.paidDiners);

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
        // Track paid diner
        if (data.diner && !this.paidDiners.includes(data.diner)) {
          this.paidDiners.push(data.diner);
          console.log('Added paid diner:', data.diner, 'paidDiners now:', this.paidDiners);
        }
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
}
