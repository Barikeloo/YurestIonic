import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import {
  filter,
  firstValueFrom,
  fromEvent,
  interval,
  Subject,
  takeUntil,
  throttleTime,
} from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { TimeTickService } from '../../../core/services/time-tick.service';
import { ToastService } from '../../../core/services/toast.service';
import { OrderTransferItem, TpvOrder, TpvOrderLine, TpvService, TpvTableItem } from '../../cash/services/tpv.service';
import { OrderStatus } from '../../../core/enums/order-status.enum';

export type PedidosAction = 'mark-to-charge' | 'cancel' | 'reopen' | 'print';

export { OrderStatus };
export type OrderTabId = 'all' | OrderStatus;
export type OrderChip = 'mine' | 'stale' | 'unpaid';

export interface OrdersFilters {
  status: string;
  user: string;
  date: string;
  search: string;
}

const DEFAULT_FILTERS: OrdersFilters = {
  status: 'all',
  user: 'all',
  date: '',
  search: '',
};

const STALE_THRESHOLD_MS = 60 * 60 * 1000;

function normalize(value: string): string {
  return value
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '');
}

@Injectable()
export class PedidosFacade {
  private readonly tpvService = inject(TpvService);
  private readonly authService = inject(AuthService);
  private readonly toastService = inject(ToastService);
  private readonly timeTick = inject(TimeTickService);

  private readonly _orders = signal<TpvOrder[]>([]);
  private readonly _users = signal<unknown[]>([]);
  private readonly _tables = signal<TpvTableItem[]>([]);
  private readonly _loading = signal<boolean>(true);

  private readonly _activeTab = signal<OrderTabId>('all');
  private readonly _filters = signal<OrdersFilters>({ ...DEFAULT_FILTERS });
  private readonly _activeChip = signal<OrderChip | null>(null);
  private readonly _currentUserId = signal<string | null>(null);

  private readonly _selectedOrder = signal<TpvOrder | null>(null);
  private readonly _selectedLines = signal<TpvOrderLine[]>([]);
  private readonly _loadingLines = signal<boolean>(false);
  private readonly _actionInProgress = signal<PedidosAction | null>(null);
  private readonly _lastRefreshedAt = signal<Date | null>(null);
  private readonly _autoRefreshEnabled = signal<boolean>(false);
  private readonly _destroy$ = new Subject<void>();

  private readonly _showTransfersFor = signal<string | null>(null);
  private readonly _transfers = signal<OrderTransferItem[]>([]);
  private readonly _loadingTransfers = signal<boolean>(false);
  private readonly _transferCounts = signal<Record<string, number>>({});

  public readonly orders: Signal<TpvOrder[]> = this._orders.asReadonly();
  public readonly users: Signal<unknown[]> = this._users.asReadonly();
  public readonly tables: Signal<TpvTableItem[]> = this._tables.asReadonly();
  public readonly loading: Signal<boolean> = this._loading.asReadonly();
  public readonly activeTab: Signal<OrderTabId> = this._activeTab.asReadonly();
  public readonly filters: Signal<OrdersFilters> = this._filters.asReadonly();
  public readonly activeChip: Signal<OrderChip | null> = this._activeChip.asReadonly();
  public readonly currentUserId: Signal<string | null> = this._currentUserId.asReadonly();
  public readonly selectedOrder: Signal<TpvOrder | null> = this._selectedOrder.asReadonly();
  public readonly selectedLines: Signal<TpvOrderLine[]> = this._selectedLines.asReadonly();
  public readonly loadingLines: Signal<boolean> = this._loadingLines.asReadonly();
  public readonly actionInProgress: Signal<PedidosAction | null> = this._actionInProgress.asReadonly();
  public readonly showTransfersFor: Signal<string | null> = this._showTransfersFor.asReadonly();
  public readonly transfers: Signal<OrderTransferItem[]> = this._transfers.asReadonly();
  public readonly loadingTransfers: Signal<boolean> = this._loadingTransfers.asReadonly();
  public readonly transferCounts: Signal<Record<string, number>> = this._transferCounts.asReadonly();

  public readonly refreshLabel: Signal<string> = computed(() => {
    const refreshed = this._lastRefreshedAt();
    if (!refreshed) {
      return '';
    }

    const seconds = Math.floor((this.timeTick.nowMs() - refreshed.getTime()) / 1000);

    return seconds < 60 ? `hace ${seconds}s` : `hace ${Math.floor(seconds / 60)}m`;
  });

  public readonly filteredOrders: Signal<TpvOrder[]> = computed(() => {
    const tab = this._activeTab();
    const filters = this._filters();
    const chip = this._activeChip();
    const currentUserId = this._currentUserId();
    let result = this._orders().slice();

    // Chip overrides on status / user
    let statusOverride: string | null = null;
    let userOverride: string | null = null;
    let extraPredicate: ((order: TpvOrder) => boolean) | null = null;

    if (chip === 'mine' && currentUserId) {
      userOverride = currentUserId;
    }

    if (chip === 'stale') {
      statusOverride = OrderStatus.OPEN;
      const now = this.timeTick.nowMs();
      extraPredicate = (order) => {
        if (!order.opened_at) {
          return false;
        }

        return now - new Date(order.opened_at).getTime() > STALE_THRESHOLD_MS;
      };
    }

    if (chip === 'unpaid') {
      statusOverride = OrderStatus.TO_CHARGE;
      extraPredicate = (order) =>
        typeof order.remaining_total === 'number' && order.remaining_total > 0;
    }

    // Status: chip override beats tab + manual filter
    if (statusOverride) {
      result = result.filter((order) => order.status === statusOverride);
    } else {
      if (tab !== 'all') {
        result = result.filter((order) => order.status === tab);
      }
      if (filters.status !== 'all') {
        result = result.filter((order) => order.status === filters.status);
      }
    }

    // User: chip override beats manual filter
    const effectiveUser = userOverride ?? filters.user;
    if (effectiveUser !== 'all') {
      result = result.filter((order) => order.opened_by_user_id === effectiveUser);
    }

    if (extraPredicate) {
      result = result.filter(extraPredicate);
    }

    if (filters.date) {
      result = result.filter((order) => order.opened_at?.startsWith(filters.date));
    }

    const rawQuery = filters.search.trim();

    if (rawQuery) {
      const query = normalize(rawQuery);
      result = result.filter((order) => this._matchesSearch(order, query));
    }

    return result;
  });

  public readonly kpiOpen: Signal<number> = computed(
    () => this._orders().filter((order) => order.status === OrderStatus.OPEN).length,
  );

  public readonly kpiInvoiced: Signal<number> = computed(
    () => this._orders().filter((order) => order.status === OrderStatus.INVOICED).length,
  );

  public readonly kpiCancelled: Signal<number> = computed(
    () => this._orders().filter((order) => order.status === OrderStatus.CANCELLED).length,
  );

  public readonly kpiToCharge: Signal<number> = computed(
    () => this._orders().filter((order) => order.status === OrderStatus.TO_CHARGE).length,
  );

  public readonly kpiTicketMedium: Signal<number> = computed(() => {
    const closed = this._orders().filter((order) => order.status === OrderStatus.INVOICED);

    if (closed.length === 0) {
      return 0;
    }

    return closed.reduce((acc, order) => acc + order.total, 0) / closed.length;
  });

  public readonly chipCounts: Signal<Record<OrderChip, number>> = computed(() => {
    const orders = this._orders();
    const currentUserId = this._currentUserId();
    const now = this.timeTick.nowMs();

    return {
      mine: currentUserId
        ? orders.filter((order) => order.opened_by_user_id === currentUserId).length
        : 0,
      stale: orders.filter(
        (order) =>
          order.status === OrderStatus.OPEN &&
          !!order.opened_at &&
          now - new Date(order.opened_at).getTime() > STALE_THRESHOLD_MS,
      ).length,
      unpaid: orders.filter(
        (order) =>
          order.status === OrderStatus.TO_CHARGE &&
          typeof order.remaining_total === 'number' &&
          order.remaining_total > 0,
      ).length,
    };
  });

  public readonly detailSubtotal: Signal<number> = computed(() =>
    this._selectedLines().reduce(
      (acc, line) => acc + Math.round((line.price * line.quantity) / (1 + line.tax_percentage / 100)),
      0,
    ),
  );

  public readonly detailTax: Signal<number> = computed(() =>
    this._selectedLines().reduce(
      (acc, line) =>
        acc + (line.price * line.quantity - Math.round((line.price * line.quantity) / (1 + line.tax_percentage / 100))),
      0,
    ),
  );

  public readonly detailTotal: Signal<number> = computed(() =>
    this._selectedLines().reduce((acc, line) => acc + line.price * line.quantity, 0),
  );

  public async loadData(preselectOrderId: string | null): Promise<void> {
    this._loading.set(true);

    try {
      const user = await firstValueFrom(this.authService.currentUser$);
      const deviceId = this.authService.getDeviceId();
      const restaurantUuid = user?.restaurantId;

      this._currentUserId.set(user?.id ?? null);

      const [orders, usersResponse, tables] = await Promise.all([
        firstValueFrom(this.tpvService.listOrders()),
        deviceId
          ? firstValueFrom(this.tpvService.listUsers(deviceId, restaurantUuid))
          : Promise.resolve({ users: [] }),
        firstValueFrom(this.tpvService.listTables()),
      ]);

      this._orders.set(orders);
      this._users.set(usersResponse.users);
      this._tables.set(tables);

      if (preselectOrderId) {
        const order = orders.find((candidate) => candidate.id === preselectOrderId) ?? null;

        if (order) {
          if (this._activeTab() !== 'all' && this._activeTab() !== order.status) {
            this._activeTab.set(order.status);
          }

          await this.selectOrder(order);
        }
      }
    } finally {
      this._loading.set(false);
      this._lastRefreshedAt.set(new Date());
    }
  }

  public async refreshOrder(orderId: string): Promise<void> {
    try {
      const refreshed = await firstValueFrom(this.tpvService.getOrder(orderId));
      this._orders.update((current) =>
        current.map((candidate) => (candidate.id === orderId ? refreshed : candidate)),
      );

      if (this._selectedOrder()?.id === orderId) {
        this._selectedOrder.set(refreshed);
        this._loadingLines.set(true);
        try {
          const lines = await firstValueFrom(this.tpvService.getOrderLines(orderId));
          this._selectedLines.set(lines);
        } catch {
          this._selectedLines.set([]);
        } finally {
          this._loadingLines.set(false);
        }
      }

      this._lastRefreshedAt.set(new Date());
    } catch {
      // Si falla el refresh individual, caemos al refresh completo
      await this.loadData(orderId);
    }
  }

  public setActiveTab(tab: OrderTabId): void {
    this._activeTab.set(tab);
    this._selectedOrder.set(null);
    this._selectedLines.set([]);
  }

  public setActiveChip(chip: OrderChip | null): void {
    this._activeChip.set(chip);
  }

  public toggleChip(chip: OrderChip): void {
    this._activeChip.update((current) => (current === chip ? null : chip));
  }

  public updateFilter<K extends keyof OrdersFilters>(key: K, value: OrdersFilters[K]): void {
    this._filters.update((current) => ({ ...current, [key]: value }));
  }

  public resetFilters(): void {
    this._filters.set({ ...DEFAULT_FILTERS });
    this._activeChip.set(null);
  }

  public async selectOrder(order: TpvOrder): Promise<void> {
    this._selectedOrder.set(order);
    this._selectedLines.set([]);
    this._loadingLines.set(true);

    try {
      const lines = await firstValueFrom(this.tpvService.getOrderLines(order.id));
      this._selectedLines.set(lines);
    } catch {
      this._selectedLines.set([]);
    } finally {
      this._loadingLines.set(false);
    }
  }

  public async markSelectedAsCharged(): Promise<void> {
    const order = this._selectedOrder();

    if (!order || this._actionInProgress() !== null) {
      return;
    }

    this._actionInProgress.set('mark-to-charge');
    try {
      const user = await firstValueFrom(this.authService.currentUser$);
      if (!user?.id) {
        throw new Error('Usuario no autenticado.');
      }
      await firstValueFrom(this.tpvService.markOrderToCharge(order.id, user.id));
      await this.refreshOrder(order.id);
      void this.toastService.presentSuccess('Pedido marcado para cobrar.');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudo marcar el pedido.';
      void this.toastService.presentError(message);
    } finally {
      this._actionInProgress.set(null);
    }
  }

  public async cancelSelected(): Promise<void> {
    const order = this._selectedOrder();

    if (!order || this._actionInProgress() !== null) {
      return;
    }

    this._actionInProgress.set('cancel');
    try {
      const user = await firstValueFrom(this.authService.currentUser$);
      if (!user?.id) {
        throw new Error('Usuario no autenticado.');
      }
      await firstValueFrom(this.tpvService.cancelOrder(order.id, user.id));
      await this.refreshOrder(order.id);
      void this.toastService.presentSuccess('Pedido cancelado.');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudo cancelar el pedido.';
      void this.toastService.presentError(message);
    } finally {
      this._actionInProgress.set(null);
    }
  }

  public async reopenSelected(): Promise<void> {
    const order = this._selectedOrder();

    if (!order || this._actionInProgress() !== null) {
      return;
    }

    this._actionInProgress.set('reopen');
    try {
      const user = await firstValueFrom(this.authService.currentUser$);
      if (!user?.id) {
        throw new Error('Usuario no autenticado.');
      }
      await firstValueFrom(this.tpvService.reopenOrder(order.id, user.id));
      await this.refreshOrder(order.id);
      void this.toastService.presentSuccess('Mesa reabierta.');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudo reabrir la mesa.';
      void this.toastService.presentError(message);
    } finally {
      this._actionInProgress.set(null);
    }
  }

  public async printSelectedTicket(): Promise<void> {
    const order = this._selectedOrder();

    if (!order || order.status !== OrderStatus.INVOICED || this._actionInProgress() !== null) {
      return;
    }

    this._actionInProgress.set('print');
    try {
      // Try thermal printer first
      await firstValueFrom(this.tpvService.printTicketOnPrinter(order.id));
      void this.toastService.presentSuccess('Ticket enviado a la impresora.');
    } catch {
      // Thermal failed (TCP error, no printer configured, etc.) → silent fallback to browser
      try {
        const ticketText = await firstValueFrom(this.tpvService.getFinalTicketText(order.id, '80'));
        this.printWindow('Ticket', order.id.slice(0, 8), ticketText);
        void this.toastService.presentSuccess('Ticket enviado a impresión.');
      } catch {
        void this.toastService.presentError('No se pudo imprimir el ticket.');
      }
    } finally {
      this._actionInProgress.set(null);
    }
  }

  public async printSelectedPreTicket(): Promise<void> {
    const order = this._selectedOrder();

    if (!order || this._actionInProgress() !== null) {
      return;
    }

    if (order.status !== OrderStatus.OPEN && order.status !== OrderStatus.TO_CHARGE) {
      return;
    }

    this._actionInProgress.set('print');
    try {
      const ticketText = await firstValueFrom(this.tpvService.getOrderPreTicketText(order.id, '80'));

      this.printWindow('Pre-cuenta', order.id.slice(0, 8), ticketText);
      void this.toastService.presentSuccess('Pre-cuenta enviada a impresión.');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudo obtener la pre-cuenta.';
      void this.toastService.presentError(message);
    } finally {
      this._actionInProgress.set(null);
    }
  }

  private printWindow(title: string, orderShortId: string, text: string): void {
    const printWindow = window.open('', '_blank', 'width=420,height=640');
    if (!printWindow) {
      void this.toastService.presentError('Activa las ventanas emergentes para imprimir.');

      return;
    }

    const safeText = text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');

    printWindow.document.write(`<!doctype html>
<html><head><meta charset="utf-8"><title>${title} ${orderShortId}</title>
<style>
  @page { margin: 0; }
  body { margin: 0; padding: 12px; font-family: 'Courier New', monospace; font-size: 12px; }
  pre { margin: 0; white-space: pre; word-break: keep-all; }
</style></head>
<body><pre>${safeText}</pre>
<script>window.onload = () => { window.print(); setTimeout(() => window.close(), 300); };</script>
</body></html>`);
    printWindow.document.close();
  }

  public async toggleTransfersPanel(orderId: string): Promise<void> {
    if (this._showTransfersFor() === orderId) {
      this._showTransfersFor.set(null);
      this._transfers.set([]);

      return;
    }

    this._showTransfersFor.set(orderId);
    this._loadingTransfers.set(true);
    try {
      const response = await firstValueFrom(this.tpvService.getOrderTransfers(orderId));
      this._transfers.set(response.transfers);
      this._transferCounts.update((map) => ({ ...map, [orderId]: response.transfers.length }));
    } catch {
      this._transfers.set([]);
      this._transferCounts.update((map) => ({ ...map, [orderId]: 0 }));
    } finally {
      this._loadingTransfers.set(false);
    }
  }

  public getTableName(tableId: string): string {
    const table = this._tables().find((candidate) => candidate.id === tableId);
    const name = table?.name ?? tableId;

    return `Mesa ${name}`;
  }

  public getUserName(userId: string | undefined): string {
    if (!userId) {
      return '';
    }

    const users = this._users() as Array<Record<string, unknown>>;
    const user = users.find((u) => (u['user_uuid'] ?? u['id']) === userId);

    return (user?.['name'] as string) ?? '';
  }

  public startAutoRefresh(): void {
    if (this._autoRefreshEnabled()) {
      return;
    }

    this._autoRefreshEnabled.set(true);

    interval(30000)
      .pipe(
        filter(() => this._autoRefreshEnabled()),
        takeUntil(this._destroy$),
      )
      .subscribe(() => {
        void this.loadData(this._selectedOrder()?.id ?? null);
      });

    fromEvent(document, 'visibilitychange')
      .pipe(
        filter(() => this._autoRefreshEnabled() && document.visibilityState === 'visible'),
        throttleTime(5000),
        takeUntil(this._destroy$),
      )
      .subscribe(() => {
        void this.loadData(this._selectedOrder()?.id ?? null);
      });
  }

  public stopAutoRefresh(): void {
    this._autoRefreshEnabled.set(false);
    this._destroy$.next();
  }

  private _matchesSearch(order: TpvOrder, normalizedQuery: string): boolean {
    const totalEuros = (order.total / 100).toFixed(2);
    const candidates = [
      order.id,
      order.id.slice(0, 8),
      this.getTableName(order.table_id),
      this.getUserName(order.opened_by_user_id),
      totalEuros,
      totalEuros.replace('.', ','),
      order.diners != null ? order.diners.toString() : '',
    ];

    return candidates.some((c) => c && normalize(c).includes(normalizedQuery));
  }
}
