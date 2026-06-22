import { computed, inject, Injectable, OnDestroy, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { EchoService } from '../../../core/services/echo.service';
import { ChargeSessionService } from '../../cash/services/charge-session.service';
import { TpvOrder, TpvOrderLine, TpvService, TpvTableItem, TpvZoneItem } from '../../cash/services/tpv.service';
import { TableService } from '../../../services/table.service';
import { OrderStatus } from '../../../core/enums/order-status.enum';

interface OrderStatusChangedEvent {
  event_type: string;
  order_id: string;
  table_id?: string;
  from_table_id?: string;
  to_table_id?: string;
}

interface TableStatusChangedEvent {
  event_type: string;
  group_id: string;
}

export interface TableWithStatus extends TpvTableItem {
  occupied: boolean;
  status?: OrderStatus;
  order_id?: string;
  diners?: number;
  opened_at?: string;
  opened_by_user_id?: string | null;
  total?: number;
  remaining_total?: number;
  merged_table_group_id?: string | null;
}

@Injectable()
export class MesasFacade implements OnDestroy {
  private readonly tpvService = inject(TpvService);
  private readonly authService = inject(AuthService);
  private readonly echoService = inject(EchoService);
  private readonly chargeSessionService = inject(ChargeSessionService);
  private readonly tableService = inject(TableService);

  private restaurantChannelName: string | null = null;
  private reloadingOrders = false;

  private readonly _zones = signal<TpvZoneItem[]>([]);
  private readonly _tables = signal<TableWithStatus[]>([]);
  private readonly _openOrders = signal<TpvOrder[]>([]);
  private readonly _activeZoneId = signal<string | null>(null);
  private readonly _selectedTable = signal<TableWithStatus | null>(null);
  private readonly _orderLines = signal<TpvOrderLine[]>([]);
  private readonly _loading = signal<boolean>(true);
  private readonly _loadingLines = signal<boolean>(false);

  public readonly zones: Signal<TpvZoneItem[]> = this._zones.asReadonly();
  public readonly tables: Signal<TableWithStatus[]> = this._tables.asReadonly();
  public readonly openOrders: Signal<TpvOrder[]> = this._openOrders.asReadonly();
  public readonly activeZoneId: Signal<string | null> = this._activeZoneId.asReadonly();
  public readonly selectedTable: Signal<TableWithStatus | null> = this._selectedTable.asReadonly();
  public readonly orderLines: Signal<TpvOrderLine[]> = this._orderLines.asReadonly();
  public readonly loading: Signal<boolean> = this._loading.asReadonly();
  public readonly loadingLines: Signal<boolean> = this._loadingLines.asReadonly();

  public readonly linesSubtotal: Signal<number> = computed(() =>
    this._orderLines().reduce(
      (acc, line) => acc + Math.round((line.price * line.quantity) / (1 + line.tax_percentage / 100)),
      0,
    ),
  );

  public readonly linesTax: Signal<number> = computed(() =>
    this._orderLines().reduce(
      (acc, line) =>
        acc + (line.price * line.quantity - Math.round((line.price * line.quantity) / (1 + line.tax_percentage / 100))),
      0,
    ),
  );

  public readonly linesTotal: Signal<number> = computed(() =>
    this._orderLines().reduce((acc, line) => acc + line.price * line.quantity, 0),
  );

  public readonly tablesByMergedGroup: Signal<Map<string, TableWithStatus[]>> = computed(() => {
    const groups = new Map<string, TableWithStatus[]>();
    const tables = this._tables();

    for (const table of tables) {
      if (table.merged_table_group_id) {
        const existing = groups.get(table.merged_table_group_id) ?? [];
        groups.set(table.merged_table_group_id, [...existing, table]);
      }
    }

    return groups;
  });

  public async loadData(): Promise<void> {
    this._loading.set(true);

    try {
      const [zones, tables, orders] = await Promise.all([
        firstValueFrom(this.tpvService.listZones()),
        firstValueFrom(this.tpvService.listTables()),
        firstValueFrom(this.tpvService.listOrders()),
      ]);

      this._zones.set(zones);

      if (zones.length > 0 && this._activeZoneId() === null) {
        this._activeZoneId.set(zones[0].id);
      }

      const activeOrders = orders.filter((order) => order.status === OrderStatus.OPEN || order.status === OrderStatus.TO_CHARGE);
      this._openOrders.set(activeOrders);

      const orderByTable = new Map<string, TpvOrder>();
      for (const order of activeOrders) {
        orderByTable.set(order.table_id, order);
      }

      const paidTotals = await this.fetchPaidTotals(activeOrders);

      const enrichedTables: TableWithStatus[] = tables.map((table) => {
        const order = orderByTable.get(table.id);
        const total = order?.total ?? 0;
        const paidTotal = order ? paidTotals.get(order.id) ?? 0 : 0;
        const remainingTotal = Math.max(0, total - paidTotal);

        return {
          ...table,
          occupied: !!order,
          status: order?.status,
          order_id: order?.id,
          diners: order?.diners,
          opened_at: order?.opened_at,
          opened_by_user_id: order?.opened_by_user_id ?? null,
          total,
          remaining_total: remainingTotal,
          merged_table_group_id: table.merged_table_group_id,
        };
      });

      this._tables.set(enrichedTables);

      if (!this.restaurantChannelName) {
        const currentUser = await firstValueFrom(this.authService.currentUser$);
        if (currentUser?.restaurantId) {
          this.subscribeToRestaurantChannel(currentUser.restaurantId);
        }
      }
    } finally {
      this._loading.set(false);
    }
  }

  private subscribeToRestaurantChannel(restaurantId: string): void {
    this.restaurantChannelName = `restaurant.${restaurantId}`;
    this.echoService.listen<OrderStatusChangedEvent>(
      this.restaurantChannelName,
      'order.status_changed',
      () => this.reloadOpenOrders(),
    );
    this.echoService.listen<TableStatusChangedEvent>(
      this.restaurantChannelName,
      'table.status_changed',
      () => this.reloadTables(),
    );
  }

  private async reloadTables(): Promise<void> {
    const tables = await firstValueFrom(this.tpvService.listTables());
    const activeOrders = this._openOrders();
    const orderByTable = new Map(activeOrders.map((o) => [o.table_id, o]));
    const paidTotals = await this.fetchPaidTotals(activeOrders);

    const enrichedTables: TableWithStatus[] = tables.map((table) => {
      const order = orderByTable.get(table.id);
      const total = order?.total ?? 0;
      const paidTotal = order ? (paidTotals.get(order.id) ?? 0) : 0;
      return {
        ...table,
        occupied: !!order,
        status: order?.status,
        order_id: order?.id,
        diners: order?.diners,
        opened_at: order?.opened_at,
        total,
        remaining_total: Math.max(0, total - paidTotal),
        merged_table_group_id: table.merged_table_group_id,
      };
    });

    this._tables.set(enrichedTables);

    const selectedId = this._selectedTable()?.id;
    if (selectedId) {
      const refreshed = enrichedTables.find((t) => t.id === selectedId) ?? null;
      this._selectedTable.set(refreshed);
    }
  }

  private async reloadOpenOrders(): Promise<void> {
    if (this.reloadingOrders) return;
    this.reloadingOrders = true;

    try {
      const orders = await firstValueFrom(this.tpvService.listOrders());
      const activeOrders = orders.filter(
        (o) => o.status === OrderStatus.OPEN || o.status === OrderStatus.TO_CHARGE,
      );
      this._openOrders.set(activeOrders);

      const orderByTable = new Map(activeOrders.map((o) => [o.table_id, o]));
      const paidTotals = await this.fetchPaidTotals(activeOrders);

      this._tables.update((tables) =>
        tables.map((table) => {
          const order = orderByTable.get(table.id);
          const total = order?.total ?? 0;
          const paidTotal = order ? (paidTotals.get(order.id) ?? 0) : 0;
          return {
            ...table,
            occupied: !!order,
            status: order?.status,
            order_id: order?.id,
            diners: order?.diners,
            opened_at: order?.opened_at,
            total,
            remaining_total: Math.max(0, total - paidTotal),
          };
        }),
      );

      const selectedId = this._selectedTable()?.id;
      if (selectedId) {
        const refreshed = this._tables().find((t) => t.id === selectedId) ?? null;
        this._selectedTable.set(refreshed);

        if (refreshed?.order_id) {
          const lines = await firstValueFrom(this.tpvService.getOrderLines(refreshed.order_id));
          this._orderLines.set(lines);
        } else {
          this._orderLines.set([]);
        }
      }
    } finally {
      this.reloadingOrders = false;
    }
  }

  public async reloadLines(): Promise<void> {
    const selected = this._selectedTable();
    if (!selected?.order_id) return;
    try {
      const lines = await firstValueFrom(this.tpvService.getOrderLines(selected.order_id));
      this._orderLines.set(lines);
    } catch (_) { }
  }

  public ngOnDestroy(): void {
    if (this.restaurantChannelName) {
      this.echoService.leaveChannel(this.restaurantChannelName);
    }
  }

  public setZone(zoneId: string): void {
    this._activeZoneId.set(zoneId);
    this._selectedTable.set(null);
    this._orderLines.set([]);
  }

  public async selectTable(table: TableWithStatus): Promise<void> {
    this._selectedTable.set(table);
    this._orderLines.set([]);

    const tablesToLoad: TableWithStatus[] = [];
    if (table.merged_table_group_id) {
      const group = this.tablesByMergedGroup().get(table.merged_table_group_id);
      if (group) {
        tablesToLoad.push(...group);
      }
    } else {
      tablesToLoad.push(table);
    }

    const orderIds = tablesToLoad
      .filter(t => t.occupied && t.order_id)
      .map(t => t.order_id!);

    if (orderIds.length === 0) {
      return;
    }

    this._loadingLines.set(true);

    try {
      const allLines: TpvOrderLine[] = [];
      for (const orderId of orderIds) {
        const lines = await firstValueFrom(this.tpvService.getOrderLines(orderId));
        allLines.push(...lines);
      }
      this._orderLines.set(allLines);
    } catch {
      this._orderLines.set([]);
    } finally {
      this._loadingLines.set(false);
    }
  }

  public getZoneName(zoneId: string): string {
    return this._zones().find((zone) => zone.id === zoneId)?.name ?? '';
  }

  public getPaidDinersForTable(table: TableWithStatus): number[] {
    if (!table.diners || !table.total || table.total <= 0) {
      return [];
    }

    const total = table.total ?? 0;
    const remaining = table.remaining_total ?? 0;
    const paidTotal = total - remaining;
    const diners = table.diners;

    const perDiner = Math.floor(total / diners);

    if (perDiner <= 0) {
      return paidTotal > 0 ? [1] : [];
    }

    const paidCount = Math.min(Math.floor(paidTotal / perDiner), diners);

    return Array.from({ length: paidCount }, (_, index) => index + 1);
  }

  public async ensureCashSessionOpen(): Promise<{ ok: true } | { ok: false; error: string }> {
    const deviceId = this.authService.getDeviceId();

    try {
      const session = await firstValueFrom(this.tpvService.getActiveCashSession(deviceId));

      if (!session || session.status !== 'open') {
        return { ok: false, error: 'La caja está cerrada. Ábrela antes de operar mesas.' };
      }

      return { ok: true };
    } catch {
      return { ok: false, error: 'No se pudo verificar el estado de la caja.' };
    }
  }

  public async createOrderForSelectedTable(diners: number): Promise<TpvOrder> {
    const table = this._selectedTable();

    if (!table) {
      throw new Error('No hay mesa seleccionada.');
    }

    const currentUser = await firstValueFrom(this.authService.currentUser$);

    if (!currentUser) {
      throw new Error('No hay sesión activa');
    }

    return firstValueFrom(
      this.tpvService.createOrder({
        table_id: table.id,
        opened_by_user_id: currentUser.id,
        diners,
      }),
    );
  }

  public async closeAccountForSelectedTable(): Promise<void> {
    const table = this._selectedTable();

    if (!table?.order_id) {
      throw new Error('No hay mesa seleccionada.');
    }

    const currentUser = await firstValueFrom(this.authService.currentUser$);

    if (!currentUser) {
      throw new Error('No hay sesión activa');
    }

    await firstValueFrom(this.tpvService.markOrderToCharge(table.order_id, currentUser.id));

    const previouslySelectedId = table.id;
    await this.loadData();

    const refreshed = this._tables().find((candidate) => candidate.id === previouslySelectedId) ?? null;
    this._selectedTable.set(refreshed);

    if (refreshed?.order_id) {
      const lines = await firstValueFrom(this.tpvService.getOrderLines(refreshed.order_id));
      this._orderLines.set(lines);
    } else {
      this._orderLines.set([]);
    }
  }

  public async fetchFreshOrder(orderId: string): Promise<TpvOrder | null> {
    try {
      return await firstValueFrom(this.tpvService.getOrder(orderId));
    } catch {
      return null;
    }
  }

  public async getPaidDinersCountFromChargeSession(orderId: string): Promise<number> {
    try {
      const session = await firstValueFrom(this.chargeSessionService.getCurrentChargeSession(orderId));

      return session?.paid_diner_numbers?.length ?? 0;
    } catch (error: unknown) {
      const httpError = error as { status?: number };

      if (httpError.status !== 404) {
        console.error('[MesasFacade] Error consultando charge session:', error);
      }

      return 0;
    }
  }

  public async updateDiners(orderId: string, diners: number): Promise<void> {
    await firstValueFrom(this.tpvService.updateOrder(orderId, { diners }));
    const previousId = this._selectedTable()?.id ?? null;
    await this.loadData();

    if (previousId) {
      const refreshed = this._tables().find((candidate) => candidate.id === previousId) ?? null;
      this._selectedTable.set(refreshed);
    }
  }

  public async transferOrderToTable(orderId: string, toTableId: string): Promise<void> {
    const currentUser = await firstValueFrom(this.authService.currentUser$);

    if (!currentUser) {
      throw new Error('No hay sesión activa');
    }

    await firstValueFrom(this.tpvService.transferOrder(orderId, {
      to_table_id: toTableId,
      transferred_by_user_id: currentUser.id,
    }));

    await this.loadData();

    const destinationSelected = this._tables().find((candidate) => candidate.id === toTableId) ?? null;
    this._selectedTable.set(destinationSelected);

    if (destinationSelected?.order_id) {
      const lines = await firstValueFrom(this.tpvService.getOrderLines(destinationSelected.order_id));
      this._orderLines.set(lines);
    } else {
      this._orderLines.set([]);
    }
  }

  public async mergeTables(tableIds: string[]): Promise<void> {
    await firstValueFrom(this.tableService.mergeTables(tableIds));
  }

  public async unmergeTables(groupId: string): Promise<void> {
    await firstValueFrom(this.tableService.unmergeTables(groupId));
  }

  private async fetchPaidTotals(orders: TpvOrder[]): Promise<Map<string, number>> {
    const paidTotals = new Map<string, number>();

    for (const order of orders) {
      try {
        const response = await firstValueFrom(this.tpvService.getOrderPaidTotal(order.id));
        paidTotals.set(order.id, response.total_cents);
      } catch (error) {
        console.error('Error fetching paid total for order:', order.id, error);
        paidTotals.set(order.id, 0);
      }
    }

    return paidTotals;
  }
}
