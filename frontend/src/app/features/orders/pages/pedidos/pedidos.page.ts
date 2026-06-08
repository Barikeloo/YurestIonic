import {
  ChangeDetectionStrategy,
  Component,
  computed,
  DestroyRef,
  inject,
  OnDestroy,
  OnInit,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { PermissionsService } from '../../../../core/services/permissions.service';
import { TimeTickService } from '../../../../core/services/time-tick.service';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { TpvOrder, TpvOrderLine } from '../../../cash/services/tpv.service';
import {
  OrderChip,
  OrdersFilters,
  OrderStatus,
  OrderTabId,
  PedidosFacade,
} from '../../facades/pedidos.facade';
import {
  ConfirmModalComponent,
  ConfirmModalVariant,
} from '../../../../shared/components/confirm-modal/confirm-modal.component';
import { LineDetailModalComponent } from '../../../../shared/components/line-detail-modal/line-detail-modal.component';
import {
  TransferDisplay,
  TransfersModalComponent,
} from '../../../../shared/components/transfers-modal/transfers-modal.component';

interface ConfirmConfig {
  title: string;
  message: string;
  confirmLabel: string;
  variant: ConfirmModalVariant;
  action: () => Promise<void>;
}

@Component({
  selector: 'app-pedidos',
  templateUrl: './pedidos.page.html',
  styleUrls: ['./pedidos.page.scss'],
  imports: [FormsModule, ConfirmModalComponent, LineDetailModalComponent, TransfersModalComponent],
  providers: [PedidosFacade],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PedidosPage implements OnInit, OnDestroy {
  protected readonly facade = inject(PedidosFacade);
  protected readonly permissions = inject(PermissionsService);
  protected readonly OrderStatus = OrderStatus;
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly timeTick = inject(TimeTickService);

  protected readonly pendingConfirm = signal<ConfirmConfig | null>(null);
  protected readonly detailModalOpen = signal<boolean>(false);
  protected readonly selectedLine = signal<TpvOrderLine | null>(null);

  protected readonly searchDisplay = signal<string>('');
  private readonly _searchInput$ = new Subject<string>();
  private readonly _destroyRef = inject(DestroyRef);

  protected readonly chips: ReadonlyArray<{ id: OrderChip; label: string; icon: string }> = [
    { id: 'mine', label: 'Mis pedidos', icon: 'user' },
    { id: 'stale', label: 'Activos > 1h', icon: 'clock' },
    { id: 'unpaid', label: 'Para cobrar pendientes', icon: 'euro' },
  ];

  constructor() {
    this._searchInput$
      .pipe(debounceTime(200), distinctUntilChanged(), takeUntilDestroyed(this._destroyRef))
      .subscribe((value) => {
        this.facade.updateFilter('search', value);
        this._syncUrl();
      });
  }

  protected readonly transfersModalTitle = computed<string>(() => {
    const order = this.facade.selectedOrder();
    if (!order) {
      return 'Transferencias';
    }

    return `Transferencias · ${this.getTableName(order.table_id)}`;
  });

  protected readonly transfersModalSubtitle = computed<string | null>(() => {
    const order = this.facade.selectedOrder();
    if (!order) {
      return null;
    }

    return `Pedido ${order.id.slice(0, 8)}...`;
  });

  protected readonly transfersDisplay = computed<TransferDisplay[]>(() =>
    this.facade.transfers().map((t) => ({
      id: t.id,
      fromTable: this.getTableName(t.from_table_id),
      toTable: this.getTableName(t.to_table_id),
      user: t.transferred_by_user_name ?? '—',
      date: this.formatDateTime(t.transferred_at),
    })),
  );

  protected readonly transfersModalOpen = computed<boolean>(() => {
    const order = this.facade.selectedOrder();
    return !!order && this.facade.showTransfersFor() === order.id;
  });

  public async ngOnInit(): Promise<void> {
    const p = this.route.snapshot.queryParams;
    const validStatuses = Object.values(OrderStatus) as string[];
    const tabParam = p['tab'] as string | undefined;

    if (tabParam && (tabParam === 'all' || validStatuses.includes(tabParam))) {
      this.facade.setActiveTab(tabParam as OrderTabId);
    }

    const status = p['status'] as string | undefined;
    if (status) this.facade.updateFilter('status', status);
    const user = p['user'] as string | undefined;
    if (user) this.facade.updateFilter('user', user);
    const date = p['date'] as string | undefined;
    if (date) this.facade.updateFilter('date', date);
    const search = p['search'] as string | undefined;
    if (search) {
      this.facade.updateFilter('search', search);
      this.searchDisplay.set(search);
    }
    const chip = p['chip'] as string | undefined;
    if (chip === 'mine' || chip === 'stale' || chip === 'unpaid') {
      this.facade.setActiveChip(chip);
    }

    const queryOrderId = p['orderId'] as string | undefined;
    await this.facade.loadData(queryOrderId ?? null);
    this._syncUrl();
    this.facade.startAutoRefresh();
  }

  public ngOnDestroy(): void {
    this.facade.stopAutoRefresh();
  }

  public setTab(tab: OrderTabId): void {
    this.facade.setActiveTab(tab);
    this._syncUrl();
  }

  public updateFilter<K extends keyof OrdersFilters>(key: K, value: OrdersFilters[K]): void {
    this.facade.updateFilter(key, value);
    this._syncUrl();
  }

  public onSearchInput(value: string): void {
    this.searchDisplay.set(value);
    this._searchInput$.next(value);
  }

  public toggleChip(chip: OrderChip): void {
    this.facade.toggleChip(chip);
    this._syncUrl();
  }

  public isChipOverridingStatus(): boolean {
    const chip = this.facade.activeChip();
    return chip === 'stale' || chip === 'unpaid';
  }

  public isChipOverridingUser(): boolean {
    return this.facade.activeChip() === 'mine';
  }

  public resetFilters(): void {
    this.facade.resetFilters();
    this.searchDisplay.set('');
    this._syncUrl();
  }

  public async selectOrder(order: TpvOrder): Promise<void> {
    await this.facade.selectOrder(order);
    this._syncUrl();
  }

  private _syncUrl(): void {
    const tab = this.facade.activeTab();
    const filters = this.facade.filters();
    const chip = this.facade.activeChip();
    const order = this.facade.selectedOrder();

    const queryParams: Record<string, string | null> = {};

    if (tab !== 'all') queryParams['tab'] = tab;
    if (filters.status !== 'all') queryParams['status'] = filters.status;
    if (filters.user !== 'all') queryParams['user'] = filters.user;
    if (filters.date) queryParams['date'] = filters.date;
    if (filters.search) queryParams['search'] = filters.search;
    if (chip) queryParams['chip'] = chip;
    if (order) queryParams['orderId'] = order.id;

    void this.router.navigate([], {
      relativeTo: this.route,
      queryParams,
      replaceUrl: true,
    });
  }

  public goToComanda(): void {
    const order = this.facade.selectedOrder();

    if (!order) {
      return;
    }

    void this.router.navigate(['/app/comanda'], { queryParams: { orderId: order.id } });
  }

  public goToCaja(): void {
    const order = this.facade.selectedOrder();

    if (!order || order.status !== OrderStatus.TO_CHARGE) {
      return;
    }

    void this.router.navigate(['/app/caja'], {
      queryParams: { orderId: order.id, fromMesas: 'true' },
    });
  }

  public goToMesa(): void {
    const order = this.facade.selectedOrder();

    if (!order) {
      return;
    }

    void this.router.navigate(['/app/mesas'], {
      queryParams: { selectedTableId: order.table_id },
    });
  }

  public markAsCharged(): void {
    const order = this.facade.selectedOrder();

    if (!order) {
      return;
    }

    this.pendingConfirm.set({
      title: 'Marcar pedido para cobrar',
      message: `¿Marcar el pedido de ${this.getTableName(order.table_id)} como "Para cobrar"?`,
      confirmLabel: 'Marcar para cobrar',
      variant: 'default',
      action: () => this.facade.markSelectedAsCharged(),
    });
  }

  public cancelOrder(): void {
    const order = this.facade.selectedOrder();

    if (!order) {
      return;
    }

    this.pendingConfirm.set({
      title: 'Cancelar pedido',
      message: `¿Cancelar el pedido de ${this.getTableName(order.table_id)}? Esta acción no se puede deshacer desde aquí.`,
      confirmLabel: 'Cancelar pedido',
      variant: 'danger',
      action: () => this.facade.cancelSelected(),
    });
  }

  public reopenOrder(): void {
    const order = this.facade.selectedOrder();

    if (!order) {
      return;
    }

    this.pendingConfirm.set({
      title: 'Volver a abrir mesa',
      message: 'El pedido pasará de "Para cobrar" a "Abierto".',
      confirmLabel: 'Volver a abrir',
      variant: 'default',
      action: () => this.facade.reopenSelected(),
    });
  }

  public openTransfers(): void {
    const order = this.facade.selectedOrder();

    if (!order || this.facade.showTransfersFor() === order.id) {
      return;
    }

    void this.facade.toggleTransfersPanel(order.id);
  }

  public closeTransfers(): void {
    const order = this.facade.selectedOrder();

    if (!order || this.facade.showTransfersFor() !== order.id) {
      return;
    }

    void this.facade.toggleTransfersPanel(order.id);
  }

  public printTicket(): Promise<void> {
    return this.facade.printSelectedTicket();
  }

  public printPreTicket(): Promise<void> {
    return this.facade.printSelectedPreTicket();
  }

  public async onConfirmModalAccept(): Promise<void> {
    const config = this.pendingConfirm();

    if (!config) {
      return;
    }

    const action = config.action;
    this.pendingConfirm.set(null);
    await action();
  }

  public onConfirmModalCancel(): void {
    this.pendingConfirm.set(null);
  }

  public getTableName(tableId: string): string {
    return this.facade.getTableName(tableId);
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + '€';
  }

  public formatTime(isoDate: string | undefined): string {
    if (!isoDate) {
      return '—';
    }

    const diffMin = Math.floor((this.timeTick.nowMs() - new Date(isoDate).getTime()) / 60000);

    if (diffMin < 60) {
      return `hace ${diffMin}m`;
    }

    const hours = Math.floor(diffMin / 60);

    if (hours < 24) {
      return `hace ${hours}h`;
    }

    return `hace ${Math.floor(hours / 24)}d`;
  }

  public lineTitle(line: TpvOrderLine): string {
    if (line.product_name) {
      let title = line.product_name;
      if (line.variant_name) {
        title += ` (${line.variant_name})`;
      }

      return title;
    }

    if (line.menu_name) {
      return line.menu_name;
    }

    return 'Producto';
  }

  public lineSubtitle(line: TpvOrderLine): string | null {
    const parts: string[] = [];

    // Menu selections
    if (line.menu_selections?.length) {
      for (const sel of line.menu_selections) {
        let selText = sel.product_name;
        if (sel.variant_name) {
          selText += ` (${sel.variant_name})`;
        }
        if (sel.modifiers?.length) {
          const modNames = sel.modifiers.map((m: { name: string }) => m.name).join(', ');
          selText += ` – ${modNames}`;
        }
        parts.push(`${sel.section_name}: ${selText}`);
      }
    }

    // Direct modifiers (non-menu lines)
    if (line.modifiers?.length) {
      const modNames = line.modifiers.map((m: { name: string }) => m.name).join(', ');
      parts.push(modNames);
    }

    if (line.diner_number) {
      parts.push(`Comensal ${line.diner_number}`);
    }

    return parts.length ? parts.join(' · ') : null;
  }

  public formatDateTime(isoDate: string | undefined): string {
    if (!isoDate) {
      return '—';
    }

    const d = new Date(isoDate);

    return d.toLocaleString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  public statusLabel(status: string): string {
    const map: Partial<Record<OrderStatus, string>> = {
      [OrderStatus.OPEN]: 'Abierto',
      [OrderStatus.TO_CHARGE]: 'Para cobrar',
      [OrderStatus.INVOICED]: 'Cerrado',
      [OrderStatus.CANCELLED]: 'Cancelado',
    };

    return map[status as OrderStatus] ?? status;
  }

  public getUserName(userId: string | undefined): string {
    if (!userId) {
      return '—';
    }

    const users = this.facade.users() as Array<Record<string, unknown>>;
    const user = users.find((u) => (u['user_uuid'] ?? u['id']) === userId);

    return (user?.['name'] as string) ?? '—';
  }

  public timeColor(isoDate: string | undefined): string {
    if (!isoDate) {
      return '';
    }

    const diffMin = Math.floor((this.timeTick.nowMs() - new Date(isoDate).getTime()) / 60000);

    if (diffMin < 15) {
      return 'time-fresh';
    }
    if (diffMin < 60) {
      return 'time-warn';
    }

    return 'time-old';
  }

  public hasUnpaid(order: TpvOrder): boolean {
    return (
      typeof order.remaining_total === 'number' &&
      order.remaining_total > 0 &&
      order.remaining_total < order.total
    );
  }

  // ── Line detail modal ──────────────────────────
  public openLineDetail(line: TpvOrderLine): void {
    this.selectedLine.set(line);
    this.detailModalOpen.set(true);
  }

  public closeLineDetail(): void {
    this.detailModalOpen.set(false);
    this.selectedLine.set(null);
  }

}
