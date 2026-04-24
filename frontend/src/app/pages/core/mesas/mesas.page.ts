import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { PinAuthModalComponent } from '../../../components/pin-auth-modal/pin-auth-modal.component';
import { AuthService, QuickAccessUserResponse } from '../../../services/auth.service';
import { TpvOrder, TpvOrderLine, TpvService, TpvTableItem, TpvZoneItem } from '../../../services/tpv.service';

interface TableWithStatus extends TpvTableItem {
  occupied: boolean;
  status?: 'open' | 'to-charge';
  order_id?: string;
  diners?: number;
  opened_at?: string;
  total?: number;
  remaining_total?: number;
}

const AVATAR_COLORS = ['#E8440A', '#1A6FE8', '#1A9E5A', '#9B59B6', '#F39C12', '#E74C3C'];

@Component({
  selector: 'app-mesas',
  templateUrl: './mesas.page.html',
  styleUrls: ['./mesas.page.scss'],
  imports: [CommonModule, PinAuthModalComponent],
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
  showPinAuthModal = false;
  diners = 1;
  openingOrder = false;
  openingError: string | null = null;
  cajaError: string | null = null;

  // Modal cerrar cuenta (mark-to-charge)
  showPinAuthModalForCloseAccount = false;
  closeAccountModalOpen = false;
  closingAccount = false;
  closeAccountError: string | null = null;

  // PIN auth para cobrar
  showPinAuthModalForCharge = false;

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

      const activeOrders = orders.filter((o) => o.status === 'open' || o.status === 'to-charge');
      this.openOrders = activeOrders;
      const orderByTable = new Map<string, TpvOrder>();
      for (const order of activeOrders) orderByTable.set(order.table_id, order);

      // Fetch paid totals for all active orders
      const paidTotals = new Map<string, number>();
      for (const order of activeOrders) {
        try {
          const response = await firstValueFrom(this.tpvService.getOrderPaidTotal(order.id));
          paidTotals.set(order.id, response.total_cents);
        } catch (error) {
          console.error('Error fetching paid total for order:', order.id, error);
          paidTotals.set(order.id, 0);
        }
      }

      this.tables = tables.map((t) => {
        const order = orderByTable.get(t.id);
        const total = order?.total || 0;
        const paidTotal = order ? paidTotals.get(order.id) || 0 : 0;
        const remainingTotal = Math.max(0, total - paidTotal);

        return {
          ...t,
          occupied: !!order,
          status: order?.status as 'open' | 'to-charge' | undefined,
          order_id: order?.id,
          diners: order?.diners,
          opened_at: order?.opened_at,
          total,
          remaining_total: remainingTotal,
        };
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
    this.cajaError = null;
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
    this.cajaError = null;
    const deviceId = this.authService.getDeviceId();
    try {
      const session = await firstValueFrom(this.tpvService.getActiveCashSession(deviceId));
      if (!session || session.status !== 'open') {
        this.cajaError = 'La caja está cerrada. Ábrela antes de operar mesas.';
        return;
      }
    } catch {
      this.cajaError = 'No se pudo verificar el estado de la caja.';
      return;
    }
    this.showPinAuthModal = true;
  }

  onPinAuthenticated(): void {
    this.showPinAuthModal = false;
    this.modalOpen = true;
    this.openingError = null;
    this.diners = 1;
  }

  closeModal(): void {
    this.modalOpen = false;
  }

  incrementDiners(): void {
    if (this.diners < 99) this.diners++;
  }

  decrementDiners(): void {
    if (this.diners > 1) this.diners--;
  }

  async confirmOpen(): Promise<void> {
    if (!this.selectedTable || this.openingOrder) return;

    this.openingOrder = true;
    this.openingError = null;

    try {
      // Obtener el usuario actual
      const currentUser = await firstValueFrom(this.authService.currentUser$);
      if (!currentUser) {
        this.openingError = 'No hay sesión activa';
        this.openingOrder = false;
        return;
      }

      // Abrir la mesa
      const order = await firstValueFrom(this.tpvService.createOrder({
        table_id: this.selectedTable.id,
        opened_by_user_id: currentUser.id,
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

  // ── Modal cerrar cuenta ───────────────────────
  async openCloseAccountModal(): Promise<void> {
    if (!this.selectedTable?.order_id) return;
    this.showPinAuthModalForCloseAccount = true;
  }

  onPinAuthenticatedForCloseAccount(): void {
    this.showPinAuthModalForCloseAccount = false;
    this.closeAccountModalOpen = true;
    this.closeAccountError = null;
  }

  closeCloseAccountModal(): void {
    this.closeAccountModalOpen = false;
  }

  async confirmCloseAccount(): Promise<void> {
    if (!this.selectedTable?.order_id || this.closingAccount) return;

    this.closingAccount = true;
    this.closeAccountError = null;

    try {
      // Obtener el usuario actual
      const currentUser = await firstValueFrom(this.authService.currentUser$);
      if (!currentUser) {
        this.closeAccountError = 'No hay sesión activa';
        this.closingAccount = false;
        return;
      }

      await firstValueFrom(this.tpvService.updateOrder(this.selectedTable.order_id, {
        action: 'mark-to-charge',
        closed_by_user_id: currentUser.id,
      }));
      this.closeAccountModalOpen = false;
      const previouslySelectedId = this.selectedTable.id;
      await this.loadData();
      const refreshed = this.tables.find((t) => t.id === previouslySelectedId) ?? null;
      this.selectedTable = refreshed;
      if (refreshed?.order_id) {
        this.orderLines = await firstValueFrom(this.tpvService.getOrderLines(refreshed.order_id));
      }
    } catch (err) {
      this.closeAccountError = err instanceof Error ? err.message : 'No se pudo cerrar la cuenta.';
    } finally {
      this.closingAccount = false;
    }
  }

  // ── Ir a cobrar (navega a caja) ───────────────────
  goToCobrar(): void {
    if (!this.selectedTable?.order_id) return;
    this.showPinAuthModalForCharge = true;
  }

  onPinAuthenticatedForCharge(): void {
    this.showPinAuthModalForCharge = false;
    if (!this.selectedTable?.order_id) return;
    void this.router.navigate(['/app/caja'], {
      queryParams: { orderId: this.selectedTable.order_id, fromMesas: 'true' },
    });
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
