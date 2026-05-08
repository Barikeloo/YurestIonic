import { Component, inject, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { TpvOrder } from '../../../cash/services/tpv.service';
import { OrdersFilters, OrderStatus, OrderTabId, PedidosFacade } from '../../facades/pedidos.facade';

@Component({
  selector: 'app-pedidos',
  templateUrl: './pedidos.page.html',
  styleUrls: ['./pedidos.page.scss'],
  imports: [FormsModule],
  providers: [PedidosFacade],
})
export class PedidosPage implements OnInit {
  protected readonly facade = inject(PedidosFacade);
  protected readonly OrderStatus = OrderStatus;
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  public async ngOnInit(): Promise<void> {
    const queryOrderId = this.route.snapshot.queryParams['orderId'] ?? null;
    await this.facade.loadData(queryOrderId);
  }

  public setTab(tab: OrderTabId): void {
    this.facade.setActiveTab(tab);
  }

  public updateFilter<K extends keyof OrdersFilters>(key: K, value: OrdersFilters[K]): void {
    this.facade.updateFilter(key, value);
  }

  public resetFilters(): void {
    this.facade.resetFilters();
  }

  public selectOrder(order: TpvOrder): Promise<void> {
    return this.facade.selectOrder(order);
  }

  public goToComanda(): void {
    const order = this.facade.selectedOrder();

    if (!order) {
      return;
    }

    void this.router.navigate(['/app/comanda'], { queryParams: { orderId: order.id } });
  }

  public goToMesa(): void {
    if (!this.facade.selectedOrder()) {
      return;
    }

    void this.router.navigate(['/app/mesas']);
  }

  public markAsCharged(): Promise<void> {
    return this.facade.markSelectedAsCharged();
  }

  public cancelOrder(): Promise<void> {
    return this.facade.cancelSelected();
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

    const diffMin = Math.floor((Date.now() - new Date(isoDate).getTime()) / 60000);

    if (diffMin < 60) {
      return `hace ${diffMin}m`;
    }

    const hours = Math.floor(diffMin / 60);

    if (hours < 24) {
      return `hace ${hours}h`;
    }

    return `hace ${Math.floor(hours / 24)}d`;
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
}
