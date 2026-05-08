
import { Component, inject, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { PinAuthModalComponent, PinAuthResult } from '../../../../components/pin-auth-modal/pin-auth-modal.component';
import { PinAuthService } from '../../../../core/services/pin-auth.service';
import { DinersStatusComponent } from '../../../../shared/components/diners-status/diners-status.component';
import { FilterByPipe } from '../../../../pipes';
import { MesasFacade, TableWithStatus } from '../../facades/mesas.facade';
import { OrderStatus } from '../../../../core/enums/order-status.enum';

const AVATAR_COLORS = ['#E8440A', '#1A6FE8', '#1A9E5A', '#9B59B6', '#F39C12', '#E74C3C'];

@Component({
  selector: 'app-mesas',
  templateUrl: './mesas.page.html',
  styleUrls: ['./mesas.page.scss'],
  imports: [PinAuthModalComponent, DinersStatusComponent, FilterByPipe],
  providers: [MesasFacade],
})
export class MesasPage implements OnInit {
  protected readonly facade = inject(MesasFacade);
  protected readonly OrderStatus = OrderStatus;
  private readonly pinAuthService = inject(PinAuthService);
  private readonly router = inject(Router);

  // ----- UI state: open-table modal -----
  public modalOpen = false;
  public showPinAuthModal = false;
  public diners = 1;
  public openingOrder = false;
  public openingError: string | null = null;
  public cajaError: string | null = null;

  // ----- UI state: close-account modal -----
  public showPinAuthModalForCloseAccount = false;
  public closeAccountModalOpen = false;
  public closingAccount = false;
  public closeAccountError: string | null = null;

  // ----- UI state: cobrar -----
  public showPinAuthModalForCharge = false;

  // ----- UI state: floating table menu -----
  public tableMenuOpen = false;
  public tableMenuTable: TableWithStatus | null = null;
  public tableMenuPosition = { x: 0, y: 0 };

  // ----- UI state: edit-diners modal -----
  public editDinersModalOpen = false;
  public editDinersValue = 1;
  public editDinersLoading = false;
  public editDinersError: string | null = null;
  public editDinersOrderId: string | null = null;
  public editDinersTable: TableWithStatus | null = null;
  public editDinersCheckingChargeSession = false;

  public async ngOnInit(): Promise<void> {
    await this.facade.loadData();
  }

  public setZone(zoneId: string): void {
    this.facade.setZone(zoneId);
  }

  public async selectTable(table: TableWithStatus): Promise<void> {
    this.cajaError = null;
    await this.facade.selectTable(table);
  }

  // ----- Open table flow -----
  public async openModal(): Promise<void> {
    this.cajaError = null;

    const result = await this.facade.ensureCashSessionOpen();

    if (!result.ok) {
      this.cajaError = result.error;

      return;
    }

    if (this.pinAuthService.requiresPin('normal')) {
      this.showPinAuthModal = true;
    } else {
      this.openOpenTableModal();
    }
  }

  public onPinAuthenticated(result: PinAuthResult): void {
    this.applyPinAuth(result);
    this.showPinAuthModal = false;
    this.openOpenTableModal();
  }

  public closeModal(): void {
    this.modalOpen = false;
  }

  public incrementDiners(): void {
    if (this.diners < 99) {
      this.diners++;
    }
  }

  public decrementDiners(): void {
    if (this.diners > 1) {
      this.diners--;
    }
  }

  public async confirmOpen(): Promise<void> {
    if (this.openingOrder) {
      return;
    }

    const selectedTable = this.facade.selectedTable();

    if (!selectedTable) {
      return;
    }

    this.openingOrder = true;
    this.openingError = null;

    try {
      const order = await this.facade.createOrderForSelectedTable(this.diners);
      this.modalOpen = false;
      void this.router.navigate(['/app/pedidos'], {
        queryParams: { orderId: order.id, tableId: selectedTable.id },
      });
    } catch (err) {
      this.openingError = err instanceof Error ? err.message : 'No se pudo abrir la mesa.';
    } finally {
      this.openingOrder = false;
    }
  }

  // ----- Close account flow -----
  public openCloseAccountModal(): void {
    if (!this.facade.selectedTable()?.order_id) {
      return;
    }

    if (this.pinAuthService.requiresPin('normal')) {
      this.showPinAuthModalForCloseAccount = true;
    } else {
      this.closeAccountModalOpen = true;
      this.closeAccountError = null;
    }
  }

  public onPinAuthenticatedForCloseAccount(result: PinAuthResult): void {
    this.applyPinAuth(result);
    this.showPinAuthModalForCloseAccount = false;
    this.closeAccountModalOpen = true;
    this.closeAccountError = null;
  }

  public closeCloseAccountModal(): void {
    this.closeAccountModalOpen = false;
  }

  public async confirmCloseAccount(): Promise<void> {
    if (this.closingAccount) {
      return;
    }

    if (!this.facade.selectedTable()?.order_id) {
      return;
    }

    this.closingAccount = true;
    this.closeAccountError = null;

    try {
      await this.facade.closeAccountForSelectedTable();
      this.closeAccountModalOpen = false;
    } catch (err) {
      this.closeAccountError = err instanceof Error ? err.message : 'No se pudo cerrar la cuenta.';
    } finally {
      this.closingAccount = false;
    }
  }

  // ----- Charge flow -----
  public goToCobrar(): void {
    if (!this.facade.selectedTable()?.order_id) {
      return;
    }

    if (this.pinAuthService.requiresPin('normal')) {
      this.showPinAuthModalForCharge = true;
    } else {
      this.navigateToCaja();
    }
  }

  public onPinAuthenticatedForCharge(result: PinAuthResult): void {
    this.applyPinAuth(result);
    this.showPinAuthModalForCharge = false;
    this.navigateToCaja();
  }

  // ----- Navigation -----
  public goToComanda(): void {
    const orderId = this.facade.selectedTable()?.order_id;

    if (orderId) {
      void this.router.navigate(['/app/comanda'], { queryParams: { orderId } });
    }
  }

  public goToPedido(): void {
    const table = this.facade.selectedTable();

    if (table?.order_id) {
      void this.router.navigate(['/app/pedidos'], {
        queryParams: { orderId: table.order_id, tableId: table.id },
      });
    }
  }

  // ----- Floating menu -----
  public openTableMenu(event: Event, table: TableWithStatus): void {
    event.stopPropagation();
    this.tableMenuTable = table;
    this.tableMenuOpen = true;
    const target = event.target as HTMLElement;
    const rect = target.getBoundingClientRect();
    this.tableMenuPosition = {
      x: rect.left + rect.width / 2 - 100,
      y: rect.bottom + 8,
    };
  }

  public closeTableMenu(): void {
    this.tableMenuOpen = false;
    this.tableMenuTable = null;
  }

  // ----- Edit-diners flow -----
  public async onEditDiners(): Promise<void> {
    const menuTable = this.tableMenuTable;
    this.closeTableMenu();

    if (!menuTable?.order_id) {
      return;
    }

    const tableInState = this.facade.tables().find((candidate) => candidate.id === menuTable.id);
    this.editDinersTable = tableInState ?? menuTable;
    this.editDinersOrderId = menuTable.order_id;

    const fresh = await this.facade.fetchFreshOrder(menuTable.order_id);

    if (fresh && this.editDinersTable) {
      this.editDinersTable = {
        ...this.editDinersTable,
        total: fresh.total,
        remaining_total: fresh.remaining_total ?? fresh.total,
        diners: fresh.diners,
      };
    }

    this.editDinersCheckingChargeSession = true;
    this.editDinersError = null;

    const paidFromSession = await this.facade.getPaidDinersCountFromChargeSession(menuTable.order_id);
    this.editDinersCheckingChargeSession = false;

    if (paidFromSession > 0) {
      this.editDinersError = `Ya hay ${paidFromSession} pago${paidFromSession === 1 ? '' : 's'} registrado${paidFromSession === 1 ? '' : 's'} en la sesión de cobro. No se puede modificar el número de comensales.`;
    } else {
      const paidDiners = this.editDinersTable
        ? this.facade.getPaidDinersForTable(this.editDinersTable)
        : [];

      if (paidDiners.length > 0) {
        this.editDinersError = `Ya hay ${paidDiners.length} pago${paidDiners.length === 1 ? '' : 's'} registrado${paidDiners.length === 1 ? '' : 's'}. No se puede modificar el número de comensales.`;
      }
    }

    this.editDinersValue = this.editDinersTable?.diners ?? 1;
    this.editDinersModalOpen = true;
  }

  public closeEditDinersModal(): void {
    this.editDinersModalOpen = false;
    this.editDinersOrderId = null;
    this.editDinersTable = null;
    this.editDinersError = null;
  }

  public get currentPaidDinersCount(): number {
    if (!this.editDinersTable) {
      return 0;
    }

    return this.facade.getPaidDinersForTable(this.editDinersTable).length;
  }

  public get canReduceDiners(): boolean {
    if (!this.editDinersTable) {
      return true;
    }

    if (this.editDinersValue < (this.editDinersTable.diners ?? 1)) {
      return this.editDinersValue >= this.currentPaidDinersCount;
    }

    return true;
  }

  public get dinersValidationMessage(): string | null {
    if (!this.editDinersTable) {
      return null;
    }

    const paidCount = this.currentPaidDinersCount;

    if (paidCount === 0) {
      return null;
    }

    if (this.editDinersValue < paidCount) {
      return `⚠️ Atención: Ya ${paidCount === 1 ? 'ha pagado' : 'han pagado'} ${paidCount} comensal${paidCount === 1 ? '' : 'es'}. Debes mantener al menos ${paidCount} comensales.`;
    }

    return null;
  }

  public incrementEditDiners(): void {
    if (this.editDinersValue < 99) {
      this.editDinersValue++;
    }
  }

  public decrementEditDiners(): void {
    if (this.editDinersValue > 1) {
      this.editDinersValue--;
    }
  }

  public async confirmEditDiners(): Promise<void> {
    if (!this.editDinersOrderId || this.editDinersLoading) {
      return;
    }

    if (!this.canReduceDiners) {
      const paidCount = this.currentPaidDinersCount;
      this.editDinersError = `No puedes reducir a ${this.editDinersValue} comensales porque ya ${paidCount === 1 ? 'ha pagado' : 'han pagado'} ${paidCount}.`;

      return;
    }

    this.editDinersLoading = true;
    this.editDinersError = null;

    try {
      await this.facade.updateDiners(this.editDinersOrderId, this.editDinersValue);
      this.closeEditDinersModal();
    } catch (err) {
      this.editDinersError = err instanceof Error ? err.message : 'Error al actualizar comensales';
    } finally {
      this.editDinersLoading = false;
    }
  }

  public onJoinTable(): void {
    this.closeTableMenu();
  }

  public onTransferAccount(): void {
    this.closeTableMenu();
  }

  // ----- Pure UI helpers -----
  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + '€';
  }

  public formatTime(isoDate: string | undefined): string {
    if (!isoDate) {
      return '';
    }

    const diffMin = Math.floor((Date.now() - new Date(isoDate).getTime()) / 60000);

    if (diffMin < 60) {
      return `hace ${diffMin} min`;
    }

    const hours = Math.floor(diffMin / 60);

    if (hours < 24) {
      return `hace ${hours}h`;
    }

    return `hace ${Math.floor(hours / 24)}d`;
  }

  public getZoneName(zoneId: string): string {
    return this.facade.getZoneName(zoneId);
  }

  public getPaidDinersForTable(table: TableWithStatus): number[] {
    return this.facade.getPaidDinersForTable(table);
  }

  public getUserInitials(name: string): string {
    const parts = name.trim().split(/\s+/);

    return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? parts[0]?.[1] ?? '');
  }

  public avatarColor(index: number): string {
    return AVATAR_COLORS[index % AVATAR_COLORS.length];
  }

  // ----- Private helpers -----
  private openOpenTableModal(): void {
    this.modalOpen = true;
    this.openingError = null;
    this.diners = 1;
  }

  private navigateToCaja(): void {
    const orderId = this.facade.selectedTable()?.order_id;

    if (!orderId) {
      return;
    }

    void this.router.navigate(['/app/caja'], {
      queryParams: { orderId, fromMesas: 'true' },
    });
  }

  private applyPinAuth(result: PinAuthResult): void {
    const now = Date.now();
    this.pinAuthService.setAuthContext({
      userId: result.userId,
      userName: result.userName,
      userRole: result.userRole,
      authenticatedAt: now,
      lastActivityAt: now,
    });
  }
}
