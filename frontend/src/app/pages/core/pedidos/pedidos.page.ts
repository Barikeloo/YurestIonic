import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { TpvOrder, TpvOrderLine, TpvService, TpvZoneItem } from '../../../services/tpv.service';

type TabId = 'all' | 'open' | 'invoiced' | 'cancelled';

@Component({
  selector: 'app-pedidos',
  templateUrl: './pedidos.page.html',
  styleUrls: ['./pedidos.page.scss'],
  imports: [CommonModule, FormsModule],
})
export class PedidosPage implements OnInit {
  orders: TpvOrder[] = [];
  zones: TpvZoneItem[] = [];
  loading = true;

  activeTab: TabId = 'all';

  filterStatus = 'all';
  filterZone = 'all';
  filterDate = '';
  filterSearch = '';

  selectedOrder: TpvOrder | null = null;
  selectedLines: TpvOrderLine[] = [];
  loadingLines = false;

  constructor(
    private readonly tpvService: TpvService,
    private readonly router: Router,
  ) {}

  async ngOnInit(): Promise<void> {
    this.loading = true;
    try {
      const [orders, zones] = await Promise.all([
        firstValueFrom(this.tpvService.listOrders()),
        firstValueFrom(this.tpvService.listZones()),
      ]);
      this.orders = orders;
      this.zones = zones;
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

  get kpiAvgTotal(): number {
    const closed = this.orders.filter((o) => o.status === 'invoiced');
    if (closed.length === 0) return 0;
    return 0; // total not available in order response
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

  // ── Helpers ────────────────────────────────────

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
    const map: Record<string, string> = { open: 'Abierto', invoiced: 'Cerrado', cancelled: 'Cancelado' };
    return map[status] ?? status;
  }
}
