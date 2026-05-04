import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { PinAuthModalComponent, PinAuthResult } from '../../../components/pin-auth-modal/pin-auth-modal.component';
import { AuthService, QuickAccessUserResponse } from '../../../services/auth.service';
import { PinAuthService } from '../../../services/pin-auth.service';
import { TpvOrder, TpvOrderLine, TpvService, TpvTableItem, TpvZoneItem } from '../../../services/tpv.service';
import { DinersStatusComponent } from '../../../components/diners-status/diners-status.component';
import { ChargeSessionService } from '../../../services/charge-session.service';

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
  imports: [CommonModule, PinAuthModalComponent, DinersStatusComponent],
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

  // Menú de opciones de mesa
  tableMenuOpen = false;
  tableMenuTable: TableWithStatus | null = null;
  tableMenuPosition = { x: 0, y: 0 };

  // Modal editar comensales
  editDinersModalOpen = false;
  editDinersValue = 1;
  editDinersLoading = false;
  editDinersError: string | null = null;
  editDinersOrderId: string | null = null;
  editDinersTable: TableWithStatus | null = null;
  editDinersCheckingChargeSession = false;

  constructor(
    private readonly tpvService: TpvService,
    private readonly authService: AuthService,
    private readonly pinAuthService: PinAuthService,
    private readonly chargeSessionService: ChargeSessionService,
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

    // Abrir mesa es acción NORMAL - verificar sesión con timeout
    if (this.pinAuthService.requiresPin('normal')) {
      this.showPinAuthModal = true;
    } else {
      // Sesión activa, abrir directamente
      this.modalOpen = true;
      this.openingError = null;
      this.diners = 1;
    }
  }

  onPinAuthenticated(result: PinAuthResult): void {
    // Guardar contexto para sesión con timeout
    const now = Date.now();
    this.pinAuthService.setAuthContext({
      userId: result.userId,
      userName: result.userName,
      userRole: result.userRole,
      authenticatedAt: now,
      lastActivityAt: now,
    });
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

    // Cerrar cuenta es acción NORMAL - verificar sesión con timeout
    if (this.pinAuthService.requiresPin('normal')) {
      this.showPinAuthModalForCloseAccount = true;
    } else {
      this.closeAccountModalOpen = true;
      this.closeAccountError = null;
    }
  }

  onPinAuthenticatedForCloseAccount(result: PinAuthResult): void {
    // Guardar contexto para sesión con timeout
    const now = Date.now();
    this.pinAuthService.setAuthContext({
      userId: result.userId,
      userName: result.userName,
      userRole: result.userRole,
      authenticatedAt: now,
      lastActivityAt: now,
    });
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

    // Cobrar es acción NORMAL - verificar sesión con timeout
    if (this.pinAuthService.requiresPin('normal')) {
      this.showPinAuthModalForCharge = true;
    } else {
      void this.router.navigate(['/app/caja'], {
        queryParams: { orderId: this.selectedTable.order_id, fromMesas: 'true' },
      });
    }
  }

  onPinAuthenticatedForCharge(result: PinAuthResult): void {
    // Guardar contexto para sesión con timeout
    const now = Date.now();
    this.pinAuthService.setAuthContext({
      userId: result.userId,
      userName: result.userName,
      userRole: result.userRole,
      authenticatedAt: now,
      lastActivityAt: now,
    });
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

  // ── Helpers para comensales ─────────────────────
  getPaidDinersForTable(table: TableWithStatus): number[] {
    if (!table.diners || !table.total || table.total <= 0) return [];

    const total = table.total ?? 0;
    const remaining = table.remaining_total ?? 0;
    const paidTotal = total - remaining;
    const diners = table.diners;

    // Calcular cuántos comensales han pagado basado en el total pagado
    const perDiner = Math.floor(total / diners);
    if (perDiner <= 0) return paidTotal > 0 ? [1] : [];

    const paidCount = Math.min(Math.floor(paidTotal / perDiner), diners);
    return Array.from({ length: paidCount }, (_, i) => i + 1);
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

  // ── Menú de opciones de mesa ───────────────────

  openTableMenu(event: Event, table: TableWithStatus): void {
    event.stopPropagation();
    this.tableMenuTable = table;
    this.tableMenuOpen = true;
    // Posicionar el menú cerca del botón clicado
    const target = event.target as HTMLElement;
    const rect = target.getBoundingClientRect();
    this.tableMenuPosition = {
      x: rect.left + rect.width / 2 - 100, // Centrado aproximado
      y: rect.bottom + 8,
    };
  }

  closeTableMenu(): void {
    this.tableMenuOpen = false;
    this.tableMenuTable = null;
  }

  async onEditDiners(): Promise<void> {
    // Guardar referencia antes de cerrar el menú
    const table = this.tableMenuTable;
    this.closeTableMenu();

    if (!table?.order_id) {
      console.log('[onEditDiners] No order_id disponible');
      return;
    }

    // Buscar tabla actualizada desde el array
    const updatedTable = this.tables.find((t) => t.id === table.id);
    this.editDinersTable = updatedTable || table;
    this.editDinersOrderId = table.order_id;

    // Recargar datos frescos de la orden para obtener remaining_total actualizado
    try {
      const freshOrder = await firstValueFrom(this.tpvService.getOrder(table.order_id));
      if (freshOrder && this.editDinersTable) {
        this.editDinersTable.total = freshOrder.total;
        this.editDinersTable.remaining_total = freshOrder.remaining_total ?? freshOrder.total;
        this.editDinersTable.diners = freshOrder.diners;
        console.log('[onEditDiners] Datos frescos recargados - total:', freshOrder.total,
          'remaining:', freshOrder.remaining_total,
          'diners:', freshOrder.diners);
      }
    } catch (e) {
      console.log('[onEditDiners] Error recargando orden (usando datos locales):', e);
    }

    // Consultar backend si hay charge session con pagos registrados
    this.editDinersCheckingChargeSession = true;
    this.editDinersError = null;

    try {
      const chargeSession = await firstValueFrom(
        this.chargeSessionService.getCurrentChargeSession(table.order_id)
      );

      // Si hay pagos registrados en la charge session, bloquear edición
      const paidCount = chargeSession?.paid_diner_numbers?.length ?? 0;
      if (paidCount > 0) {
        this.editDinersError = `Ya hay ${paidCount} pago${paidCount === 1 ? '' : 's'} registrado${paidCount === 1 ? '' : 's'} en la sesión de cobro. No se puede modificar el número de comensales.`;
        this.editDinersCheckingChargeSession = false;
        // Mostrar el modal con el mensaje de error (pero deshabilitado)
        this.editDinersValue = this.editDinersTable.diners ?? 1;
        this.editDinersModalOpen = true;
        return;
      }
    } catch (error: unknown) {
      // 404 = no hay sesión activa, es seguro continuar
      const httpError = error as { status?: number };
      if (httpError.status !== 404) {
        console.error('[onEditDiners] Error consultando charge session:', error);
        // No bloqueamos por errores de red, usamos la validación local como fallback
      }
    } finally {
      this.editDinersCheckingChargeSession = false;
    }

    // Validación local como respaldo (si no hay charge session o hubo error de red)
    const paidDiners = this.getPaidDinersForTable(this.editDinersTable);
    if (paidDiners.length > 0) {
      this.editDinersError = `Ya hay ${paidDiners.length} pago${paidDiners.length === 1 ? '' : 's'} registrado${paidDiners.length === 1 ? '' : 's'}. No se puede modificar el número de comensales.`;
    } else {
      this.editDinersError = null;
    }

    this.editDinersValue = this.editDinersTable.diners ?? 1;
    this.editDinersModalOpen = true;
    console.log('[onEditDiners] Modal abierto para', this.editDinersTable?.name,
      'con', this.editDinersValue, 'comensales');
  }

  closeEditDinersModal(): void {
    this.editDinersModalOpen = false;
    this.editDinersOrderId = null;
    this.editDinersTable = null;
    this.editDinersError = null;
  }

  // ── Validación de edición de comensales ─────────────────────

  get currentPaidDinersCount(): number {
    if (!this.editDinersTable) return 0;
    const paidDiners = this.getPaidDinersForTable(this.editDinersTable);
    console.log('[currentPaidDinersCount] Table:', this.editDinersTable.name, 'total:', this.editDinersTable.total, 'remaining:', this.editDinersTable.remaining_total, 'diners:', this.editDinersTable.diners, 'paidDiners:', paidDiners);
    return paidDiners.length;
  }

  get canReduceDiners(): boolean {
    // Si no hay datos de la mesa, permitir (fallback seguro)
    if (!this.editDinersTable) return true;

    // Si se intenta reducir comensales
    if (this.editDinersValue < (this.editDinersTable.diners ?? 1)) {
      const paidCount = this.currentPaidDinersCount;
      const canReduce = this.editDinersValue >= paidCount;
      console.log('[canReduceDiners] editDinersValue:', this.editDinersValue, 'paidCount:', paidCount, 'canReduce:', canReduce);
      return canReduce;
    }

    // Si se aumentan o mantienen, siempre permitir
    return true;
  }

  get dinersValidationMessage(): string | null {
    if (!this.editDinersTable) return null;
    
    const paidCount = this.currentPaidDinersCount;
    if (paidCount === 0) return null;
    
    if (this.editDinersValue < paidCount) {
      return `⚠️ Atención: Ya ${paidCount === 1 ? 'ha pagado' : 'han pagado'} ${paidCount} comensal${paidCount === 1 ? '' : 'es'}. Debes mantener al menos ${paidCount} comensales.`;
    }
    
    return null;
  }

  incrementEditDiners(): void {
    if (this.editDinersValue < 99) {
      this.editDinersValue++;
    }
  }

  decrementEditDiners(): void {
    if (this.editDinersValue > 1) {
      this.editDinersValue--;
    }
  }

  async confirmEditDiners(): Promise<void> {
    if (!this.editDinersOrderId || this.editDinersLoading) return;

    // Validar antes de enviar
    if (!this.canReduceDiners) {
      const paidCount = this.currentPaidDinersCount;
      this.editDinersError = `No puedes reducir a ${this.editDinersValue} comensales porque ya ${paidCount === 1 ? 'ha pagado' : 'han pagado'} ${paidCount}.`;
      return;
    }

    this.editDinersLoading = true;
    this.editDinersError = null;

    try {
      await firstValueFrom(
        this.tpvService.updateOrder(this.editDinersOrderId, {
          diners: this.editDinersValue,
        })
      );

      // Recargar datos para reflejar cambio
      await this.loadData();

      // Actualizar selectedTable con los nuevos datos
      if (this.selectedTable) {
        const updatedTable = this.tables.find((t) => t.id === this.selectedTable!.id);
        if (updatedTable) {
          this.selectedTable = updatedTable;
        }
      }

      this.closeEditDinersModal();
    } catch (err: unknown) {
      const errorMsg = err instanceof Error ? err.message : 'Error al actualizar comensales';
      this.editDinersError = errorMsg;
    } finally {
      this.editDinersLoading = false;
    }
  }

  onJoinTable(): void {
    console.log('[Menu] Juntar mesa:', this.tableMenuTable?.name);
    this.closeTableMenu();
    // TODO: Implementar juntar mesa
  }

  onTransferAccount(): void {
    console.log('[Menu] Traspasar cuenta:', this.tableMenuTable?.name);
    this.closeTableMenu();
    // TODO: Implementar traspaso de cuenta
  }
}
