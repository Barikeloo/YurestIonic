import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { AuthService, QuickAccessUserResponse } from '../../../services/auth.service';
import { TpvOrder, TpvOrderLine, TpvService, TpvTableItem, TpvZoneItem } from '../../../services/tpv.service';

interface TableWithStatus extends TpvTableItem {
  occupied: boolean;
  order_id?: string;
  diners?: number;
  opened_at?: string;
}

const AVATAR_COLORS = ['#E8440A', '#1A6FE8', '#1A9E5A', '#9B59B6', '#F39C12', '#E74C3C'];

@Component({
  selector: 'app-mesas',
  templateUrl: './mesas.page.html',
  styleUrls: ['./mesas.page.scss'],
  imports: [CommonModule],
})
export class MesasPage implements OnInit {
  zones: TpvZoneItem[] = [];
  tables: TableWithStatus[] = [];
  openOrders: TpvOrder[] = [];
  activeZoneId: string | null = null;
  selectedTable: TableWithStatus | null = null;
  orderLines: TpvOrderLine[] = [];
  loadingLines = false;
  loading = true;

  // Modal apertura
  modalOpen = false;
  quickUsers: QuickAccessUserResponse[] = [];
  selectedOperator: QuickAccessUserResponse | null = null;
  diners = 1;
  openingOrder = false;
  openingError: string | null = null;

  constructor(
    private readonly tpvService: TpvService,
    private readonly authService: AuthService,
    private readonly router: Router,
  ) {}

  async ngOnInit(): Promise<void> {
    await this.loadData();
  }

  async loadData(): Promise<void> {
    this.loading = true;
    try {
      const [zones, tables, orders] = await Promise.all([
        firstValueFrom(this.tpvService.listZones()),
        firstValueFrom(this.tpvService.listTables()),
        firstValueFrom(this.tpvService.listOrders()),
      ]);

      this.zones = zones;
      if (zones.length > 0) this.activeZoneId = zones[0].id;

      const openOrders = orders.filter((o) => o.status === 'open');
      this.openOrders = openOrders;
      const orderByTable = new Map<string, TpvOrder>();
      for (const order of openOrders) orderByTable.set(order.table_id, order);

      this.tables = tables.map((t) => {
        const order = orderByTable.get(t.id);
        return { ...t, occupied: !!order, order_id: order?.id, diners: order?.diners, opened_at: order?.opened_at };
      });
    } finally {
      this.loading = false;
    }
  }

  get tablesForActiveZone(): TableWithStatus[] {
    return this.tables.filter((t) => t.zone_id === this.activeZoneId);
  }

  setZone(zoneId: string): void {
    this.activeZoneId = zoneId;
    this.selectedTable = null;
    this.orderLines = [];
  }

  async selectTable(table: TableWithStatus): Promise<void> {
    this.selectedTable = table;
    this.orderLines = [];
    if (table.occupied && table.order_id) {
      this.loadingLines = true;
      try {
        this.orderLines = await firstValueFrom(this.tpvService.getOrderLines(table.order_id));
      } catch {
        this.orderLines = [];
      } finally {
        this.loadingLines = false;
      }
    }
  }

  // ── Modal apertura ────────────────────────────
  async openModal(): Promise<void> {
    this.modalOpen = true;
    this.openingError = null;
    this.diners = 1;
    try {
      const deviceId = this.authService.getDeviceId();
      const user = await firstValueFrom(this.authService.currentUser$);
      const restaurantUuid = user?.restaurantId;
      this.quickUsers = await firstValueFrom(this.authService.getQuickUsers(deviceId, restaurantUuid));
      if (this.quickUsers.length > 0) this.selectedOperator = this.quickUsers[0];
    } catch {
      this.quickUsers = [];
    }
  }

  closeModal(): void {
    this.modalOpen = false;
  }

  selectOperator(user: QuickAccessUserResponse): void {
    this.selectedOperator = user;
  }

  numpadPress(key: string): void {
    if (key === 'del') {
      if (this.diners >= 10) {
        this.diners = Math.floor(this.diners / 10);
      } else {
        this.diners = 1;
      }
      return;
    }
    const n = parseInt(key, 10);
    if (this.diners === 1 && n > 0) {
      this.diners = n;
    } else if (this.diners < 100) {
      this.diners = parseInt(String(this.diners) + key, 10);
    }
  }

  async confirmOpen(): Promise<void> {
    if (!this.selectedTable || !this.selectedOperator || this.openingOrder) return;
    this.openingOrder = true;
    this.openingError = null;
    try {
      const order = await firstValueFrom(this.tpvService.createOrder({
        table_id: this.selectedTable.id,
        opened_by_user_id: this.selectedOperator.user_uuid,
        diners: this.diners,
      }));
      this.modalOpen = false;
      void this.router.navigate(['/app/pedidos'], {
        queryParams: { orderId: order.id, tableId: this.selectedTable.id },
      });
    } catch (err) {
      this.openingError = err instanceof Error ? err.message : 'No se pudo abrir la mesa.';
    } finally {
      this.openingOrder = false;
    }
  }

  // ── Panel ──────────────────────────────────────
  goToComanda(): void {
    if (this.selectedTable?.order_id) {
      void this.router.navigate(['/app/comanda'], {
        queryParams: { orderId: this.selectedTable.order_id },
      });
    }
  }

  goToPedido(): void {
    if (this.selectedTable?.order_id) {
      void this.router.navigate(['/app/pedidos'], {
        queryParams: { orderId: this.selectedTable.order_id, tableId: this.selectedTable.id },
      });
    }
  }

  // ── Helpers ───────────────────────────────────
  get linesSubtotal(): number {
    return this.orderLines.reduce((acc, l) => acc + Math.round((l.price * l.quantity) / (1 + l.tax_percentage / 100)), 0);
  }

  get linesTax(): number {
    return this.orderLines.reduce((acc, l) => acc + (l.price * l.quantity - Math.round((l.price * l.quantity) / (1 + l.tax_percentage / 100))), 0);
  }

  get linesTotal(): number {
    return this.orderLines.reduce((acc, l) => acc + l.price * l.quantity, 0);
  }

  formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + '€';
  }

  formatTime(isoDate: string | undefined): string {
    if (!isoDate) return '';
    const diffMin = Math.floor((Date.now() - new Date(isoDate).getTime()) / 60000);
    if (diffMin < 60) return `hace ${diffMin} min`;
    const h = Math.floor(diffMin / 60);
    if (h < 24) return `hace ${h}h`;
    return `hace ${Math.floor(h / 24)}d`;
  }

  getZoneName(zoneId: string): string {
    return this.zones.find((z) => z.id === zoneId)?.name ?? '';
  }

  getUserInitials(name: string): string {
    const parts = name.trim().split(/\s+/);
    return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? parts[0]?.[1] ?? '');
  }

  avatarColor(index: number): string {
    return AVATAR_COLORS[index % AVATAR_COLORS.length];
  }
}
