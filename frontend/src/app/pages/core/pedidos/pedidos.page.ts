import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { AuthService } from '../../../services/auth.service';
import { TpvOrder, TpvOrderLine, TpvService, TpvZoneItem } from '../../../services/tpv.service';

type TabId = 'all' | 'open' | 'to-charge' | 'invoiced' | 'cancelled';

@Component({
  selector: 'app-pedidos',
  templateUrl: './pedidos.page.html',
  styleUrls: ['./pedidos.page.scss'],
  imports: [CommonModule, FormsModule],
})
export class PedidosPage implements OnInit {
  orders: TpvOrder[] = [];
  zones: TpvZoneItem[] = [];
  users: any[] = [];
  tables: any[] = [];
  loading = true;

  activeTab: TabId = 'all';

  filterStatus = 'all';
  filterZone = 'all';
  filterUser = 'all';
  filterDate = '';
  filterSearch = '';

  selectedOrder: TpvOrder | null = null;
  selectedLines: TpvOrderLine[] = [];
  loadingLines = false;

  constructor(
    private readonly tpvService: TpvService,
    private readonly router: Router,
    private readonly authService: AuthService,
  ) {}

  async ngOnInit(): Promise<void> {
    this.loading = true;
    try {
      const user = await firstValueFrom(this.authService.currentUser$);
      const deviceId = this.authService.getDeviceId();
      const restaurantUuid = user?.restaurantId;

      const [orders, zones, usersResponse, tables] = await Promise.all([
        firstValueFrom(this.tpvService.listOrders()),
        firstValueFrom(this.tpvService.listZones()),
        deviceId ? firstValueFrom(this.tpvService.listUsers(deviceId, restaurantUuid)) : Promise.resolve({ users: [] }),
        firstValueFrom(this.tpvService.listTables()),
      ]);
      this.orders = orders;
      this.zones = zones;
      this.users = usersResponse.users;
      this.tables = tables;
    } finally {
      this.loading = false;
    }
  }

  // ── Tabs ───────────────────────────────────────

  setTab(tab: TabId): void {
    this.activeTab = tab;
    this.selectedOrder = null;
    this.selectedLines = [];
  }

  // ── Filtering ──────────────────────────────────

  get filteredOrders(): TpvOrder[] {
    let result = [...this.orders];

    if (this.activeTab !== 'all') {
      result = result.filter((o) => o.status === this.activeTab);
    }

    if (this.filterStatus !== 'all') {
      result = result.filter((o) => o.status === this.filterStatus);
    }

    if (this.filterUser !== 'all') {
      result = result.filter((o) => o.opened_by_user_id === this.filterUser);
    }

    if (this.filterDate) {
      result = result.filter((o) => o.opened_at?.startsWith(this.filterDate));
    }

    const q = this.filterSearch.trim().toLowerCase();
    if (q) {
      result = result.filter((o) =>
        o.id.toLowerCase().includes(q),
      );
    }

    return result;
  }

  resetFilters(): void {
    this.filterStatus = 'all';
    this.filterZone = 'all';
    this.filterUser = 'all';
    this.filterDate = '';
    this.filterSearch = '';
  }

  // ── KPIs ───────────────────────────────────────

  get kpiOpen(): number {
    return this.orders.filter((o) => o.status === 'open').length;
  }

  get kpiInvoiced(): number {
    return this.orders.filter((o) => o.status === 'invoiced').length;
  }

  get kpiCancelled(): number {
    return this.orders.filter((o) => o.status === 'cancelled').length;
  }

  get kpiToCharge(): number {
    return this.orders.filter((o) => o.status === 'to-charge').length;
  }

  get kpiTicketMedium(): number {
    const closed = this.orders.filter((o) => o.status === 'invoiced');
    if (closed.length === 0) return 0;
    const totalSum = closed.reduce((acc, o) => acc + o.total, 0);
    return totalSum / closed.length;
  }

  // ── Detail ─────────────────────────────────────

  async selectOrder(order: TpvOrder): Promise<void> {
    this.selectedOrder = order;
    this.selectedLines = [];
    this.loadingLines = true;
    try {
      this.selectedLines = await firstValueFrom(this.tpvService.getOrderLines(order.id));
    } catch {
      this.selectedLines = [];
    } finally {
      this.loadingLines = false;
    }
  }

  get detailSubtotal(): number {
    return this.selectedLines.reduce(
      (acc, l) => acc + Math.round((l.price * l.quantity) / (1 + l.tax_percentage / 100)),
      0,
    );
  }

  get detailTax(): number {
    return this.selectedLines.reduce(
      (acc, l) => acc + (l.price * l.quantity - Math.round((l.price * l.quantity) / (1 + l.tax_percentage / 100))),
      0,
    );
  }

  get detailTotal(): number {
    return this.selectedLines.reduce((acc, l) => acc + l.price * l.quantity, 0);
  }

  goToComanda(): void {
    if (!this.selectedOrder) return;
    void this.router.navigate(['/app/comanda'], {
      queryParams: { orderId: this.selectedOrder.id },
    });
  }

  goToMesa(): void {
    if (!this.selectedOrder) return;
    void this.router.navigate(['/app/mesas']);
  }

  async markAsCharged(): Promise<void> {
    if (!this.selectedOrder) return;
    // TODO: Implementar llamada al backend para cambiar estado a 'to-charge'
    console.log('Marcar como cobrado:', this.selectedOrder.id);
  }

  async cancelOrder(): Promise<void> {
    if (!this.selectedOrder) return;
    // TODO: Implementar llamada al backend para cambiar estado a 'cancelled'
    console.log('Cancelar pedido:', this.selectedOrder.id);
  }

  // ── Helpers ────────────────────────────────────

  getTableName(tableId: string): string {
    const table = this.tables.find((t) => t.id === tableId);
    const name = table?.name ?? tableId;
    return `Mesa ${name}`;
  }

  formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + '€';
  }

  formatTime(isoDate: string | undefined): string {
    if (!isoDate) return '—';
    const diffMin = Math.floor((Date.now() - new Date(isoDate).getTime()) / 60000);
    if (diffMin < 60) return `hace ${diffMin}m`;
    const h = Math.floor(diffMin / 60);
    if (h < 24) return `hace ${h}h`;
    return `hace ${Math.floor(h / 24)}d`;
  }

  statusLabel(status: string): string {
    const map: Record<string, string> = { open: 'Abierto', 'to-charge': 'Para cobrar', invoiced: 'Cerrado', cancelled: 'Cancelado' };
    return map[status] ?? status;
  }
}
