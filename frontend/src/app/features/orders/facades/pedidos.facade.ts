import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { TpvOrder, TpvOrderLine, TpvService, TpvTableItem } from '../../cash/services/tpv.service';
import { OrderStatus } from '../../../core/enums/order-status.enum';

export { OrderStatus };
export type OrderTabId = 'all' | OrderStatus;

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

@Injectable()
export class PedidosFacade {
  private readonly tpvService = inject(TpvService);
  private readonly authService = inject(AuthService);

  private readonly _orders = signal<TpvOrder[]>([]);
  private readonly _users = signal<unknown[]>([]);
  private readonly _tables = signal<TpvTableItem[]>([]);
  private readonly _loading = signal<boolean>(true);

  private readonly _activeTab = signal<OrderTabId>('all');
  private readonly _filters = signal<OrdersFilters>({ ...DEFAULT_FILTERS });

  private readonly _selectedOrder = signal<TpvOrder | null>(null);
  private readonly _selectedLines = signal<TpvOrderLine[]>([]);
  private readonly _loadingLines = signal<boolean>(false);

  public readonly orders: Signal<TpvOrder[]> = this._orders.asReadonly();
  public readonly users: Signal<unknown[]> = this._users.asReadonly();
  public readonly tables: Signal<TpvTableItem[]> = this._tables.asReadonly();
  public readonly loading: Signal<boolean> = this._loading.asReadonly();
  public readonly activeTab: Signal<OrderTabId> = this._activeTab.asReadonly();
  public readonly filters: Signal<OrdersFilters> = this._filters.asReadonly();
  public readonly selectedOrder: Signal<TpvOrder | null> = this._selectedOrder.asReadonly();
  public readonly selectedLines: Signal<TpvOrderLine[]> = this._selectedLines.asReadonly();
  public readonly loadingLines: Signal<boolean> = this._loadingLines.asReadonly();

  public readonly filteredOrders: Signal<TpvOrder[]> = computed(() => {
    const tab = this._activeTab();
    const filters = this._filters();
    let result = this._orders().slice();

    if (tab !== 'all') {
      result = result.filter((order) => order.status === tab);
    }

    if (filters.status !== 'all') {
      result = result.filter((order) => order.status === filters.status);
    }

    if (filters.user !== 'all') {
      result = result.filter((order) => order.opened_by_user_id === filters.user);
    }

    if (filters.date) {
      result = result.filter((order) => order.opened_at?.startsWith(filters.date));
    }

    const query = filters.search.trim().toLowerCase();

    if (query) {
      result = result.filter((order) => order.id.toLowerCase().includes(query));
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
    }
  }

  public setActiveTab(tab: OrderTabId): void {
    this._activeTab.set(tab);
    this._selectedOrder.set(null);
    this._selectedLines.set([]);
  }

  public updateFilter<K extends keyof OrdersFilters>(key: K, value: OrdersFilters[K]): void {
    this._filters.update((current) => ({ ...current, [key]: value }));
  }

  public resetFilters(): void {
    this._filters.set({ ...DEFAULT_FILTERS });
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

    if (!order) {
      return;
    }

    const user = await firstValueFrom(this.authService.currentUser$);
    await firstValueFrom(
      this.tpvService.updateOrder(order.id, {
        action: 'mark-to-charge',
        closed_by_user_id: user?.id,
      }),
    );
    await this.loadData(order.id);
  }

  public async cancelSelected(): Promise<void> {
    const order = this._selectedOrder();

    if (!order) {
      return;
    }

    const user = await firstValueFrom(this.authService.currentUser$);
    await firstValueFrom(
      this.tpvService.updateOrder(order.id, {
        action: 'cancel',
        closed_by_user_id: user?.id,
      }),
    );
    await this.loadData(order.id);
  }

  public getTableName(tableId: string): string {
    const table = this._tables().find((candidate) => candidate.id === tableId);
    const name = table?.name ?? tableId;

    return `Mesa ${name}`;
  }
}
