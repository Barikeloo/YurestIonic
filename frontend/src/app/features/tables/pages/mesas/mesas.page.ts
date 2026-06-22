import { Component, computed, inject, OnInit, OnDestroy, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { PinAuthModalComponent, PinAuthResult } from '../../../../components/pin-auth-modal/pin-auth-modal.component';
import { PinAuthService } from '../../../../core/services/pin-auth.service';
import { ToastService } from '../../../../core/services/toast.service';
import { DinersStatusComponent } from '../../../../shared/components/diners-status/diners-status.component';
import { FilterByPipe } from '../../../../pipes';
import { MesasFacade, TableWithStatus } from '../../facades/mesas.facade';
import { OrderStatus } from '../../../../core/enums/order-status.enum';
import { AuthActionType } from '../../../../core/enums/auth-action-type.enum';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { OrderTransferItem, TpvOrderLine, TpvService } from '../../../cash/services/tpv.service';
import { firstValueFrom } from 'rxjs';
import { TransferTableModalComponent } from '../../ui/transfer-table-modal/transfer-table-modal.component';
import { LineDetailModalComponent } from '../../../../shared/components/line-detail-modal/line-detail-modal.component';
import { FloorPlanComponent } from '../../ui/floor-plan/floor-plan.component';
import { QrTokenModalComponent } from '../../ui/qr-token-modal/qr-token-modal.component';
import { EchoService } from '../../../../core/services/echo.service';
import { AuthService } from '../../../../core/services/auth.service';
import { CommonModule } from '@angular/common';

const AVATAR_COLORS = ['#E8440A', '#1A6FE8', '#1A9E5A', '#9B59B6', '#F39C12', '#E74C3C'];

@Component({
  selector: 'app-mesas',
  templateUrl: './mesas.page.html',
  styleUrls: ['./mesas.page.scss'],
  imports: [CommonModule, PinAuthModalComponent, DinersStatusComponent, FilterByPipe, DragDropModule, TransferTableModalComponent, LineDetailModalComponent, FloorPlanComponent, QrTokenModalComponent],
  providers: [MesasFacade],
})
export class MesasPage implements OnInit, OnDestroy {
  protected readonly facade = inject(MesasFacade);
  protected readonly OrderStatus = OrderStatus;
  private readonly pinAuthService = inject(PinAuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly toastService = inject(ToastService);
  private readonly tpvService = inject(TpvService);
  private readonly echoService = inject(EchoService);
  private readonly authService = inject(AuthService);

  public readonly viewMode = signal<'lista' | 'plano'>(
    (localStorage.getItem('mesas_view_mode') as 'lista' | 'plano') ?? 'lista'
  );

  public readonly activeZoneTables = computed<TableWithStatus[]>(() =>
    this.facade.tables().filter(t => t.zone_id === this.facade.activeZoneId())
  );

  public setViewMode(mode: 'lista' | 'plano'): void {
    this.viewMode.set(mode);
    localStorage.setItem('mesas_view_mode', mode);
  }

  public onFloorTableSelected(table: TableWithStatus): void {
    if (this.isMergeMode) {
      this.toggleTableForMerge(table.id);
    } else {
      void this.selectTable(table);
    }
  }

  public modalOpen = false;
  public showPinAuthModal = false;
  public diners = 1;
  public openingOrder = false;

  public showPinAuthModalForCloseAccount = false;
  public closeAccountModalOpen = false;
  public closingAccount = false;

  public showPinAuthModalForCharge = false;

  public tableMenuOpen = false;
  public tableMenuTable: TableWithStatus | null = null;
  public tableMenuPosition = { x: 0, y: 0 };

  public editDinersModalOpen = false;
  public editDinersValue = 1;
  public editDinersLoading = false;
  public editDinersError: string | null = null;
  public editDinersOrderId: string | null = null;
  public editDinersTable: TableWithStatus | null = null;
  public editDinersCheckingChargeSession = false;

  public detailModalOpen = false;
  public selectedLine: TpvOrderLine | null = null;

  public transferModalOpen = false;
  public transferLoading = false;
  public transferError: string | null = null;
  public transferSourceTable: TableWithStatus | null = null;
  public transferHasPartialPayments = false;

  public lastTransfer: OrderTransferItem | null = null;

  private static readonly ANCHOR_KEY = 'mesas_merge_anchors';

  public isMergeMode = false;
  public selectedTablesForMerge: string[] = [];
  public mergingTables = false;
  public dragTargetId: string | null = null;
  public dragSourceTableId: string | null = null;
  public mergeAnchorTableIds: string[] = JSON.parse(
    localStorage.getItem(MesasPage.ANCHOR_KEY) ?? '[]'
  );
  private dragSourceTable: TableWithStatus | null = null;
  private dragSourceElement: HTMLElement | null = null;
  private dragPreview: HTMLElement | null = null;
  private dragStartX = 0;
  private dragStartY = 0;
  private dragOffsetX = 0;
  private dragOffsetY = 0;
  private readonly DRAG_THRESHOLD = 8;

  public qrModalTable = signal<TableWithStatus | null>(null);
  public readonly checkRequestedAlert = signal<{ guestName: string | null; orderId: string } | null>(null);
  private guestChannelName: string | null = null;

  public openQrModal(table: TableWithStatus): void {
    this.qrModalTable.set(table);
  }

  public closeQrModal(): void {
    this.qrModalTable.set(null);
  }

  public isGuestOpenedTable(table: TableWithStatus): boolean {
    return !!table.occupied && !table.opened_by_user_id;
  }

  public dismissCheckAlert(): void {
    this.checkRequestedAlert.set(null);
  }

  private subscribeToGuestEvents(restaurantId: string): void {
    this.guestChannelName = `restaurant.${restaurantId}`;
    this.echoService.listen<{ order_id: string; guest_name: string | null }>(
      this.guestChannelName,
      'guest.check_requested',
      (data) => {
        this.checkRequestedAlert.set({ guestName: data.guest_name, orderId: data.order_id });
        void this.facade.reloadLines();
      },
    );
    this.echoService.listen(
      this.guestChannelName,
      'guest.round_submitted',
      () => void this.facade.reloadLines(),
    );
  }

  public ngOnDestroy(): void {
    if (this.guestChannelName) {
      this.echoService.leaveChannel(this.guestChannelName);
    }
  }

  public async ngOnInit(): Promise<void> {
    await this.facade.loadData();
    this.pruneAnchors();

    firstValueFrom(this.authService.currentUser$)
      .then((user) => { if (user?.restaurantId) this.subscribeToGuestEvents(user.restaurantId); })
      .catch(() => {});

    const preselectId = this.route.snapshot.queryParams['selectedTableId'] ?? null;

    if (preselectId) {
      const table = this.facade.tables().find((candidate) => candidate.id === preselectId);

      if (table) {
        await this.selectTable(table);
      }
    }
  }

  private addAnchor(tableId: string): void {
    if (!this.mergeAnchorTableIds.includes(tableId)) {
      this.mergeAnchorTableIds = [...this.mergeAnchorTableIds, tableId];
    }
    localStorage.setItem(MesasPage.ANCHOR_KEY, JSON.stringify(this.mergeAnchorTableIds));
  }

  private pruneAnchors(): void {
    const mergedIds = new Set(
      this.facade.tables().filter(t => !!t.merged_table_group_id).map(t => t.id)
    );
    this.mergeAnchorTableIds = this.mergeAnchorTableIds.filter(id => mergedIds.has(id));
    localStorage.setItem(MesasPage.ANCHOR_KEY, JSON.stringify(this.mergeAnchorTableIds));
  }

  public setZone(zoneId: string): void {
    this.facade.setZone(zoneId);
  }

  public async selectTable(table: TableWithStatus): Promise<void> {
    await this.facade.selectTable(table);
    await this.loadLastTransferForOrder(table.order_id ?? null);
  }

  private async loadLastTransferForOrder(orderId: string | null): Promise<void> {
    if (!orderId) {
      this.lastTransfer = null;

      return;
    }

    try {
      const response = await firstValueFrom(this.tpvService.getOrderTransfers(orderId));
      this.lastTransfer = response.transfers[0] ?? null;
    } catch {
      this.lastTransfer = null;
    }
  }

  public get lastTransferFromTableName(): string | null {
    if (!this.lastTransfer) return null;

    return this.facade.tables().find((t) => t.id === this.lastTransfer!.from_table_id)?.name ?? null;
  }

  public get lastTransferTimeFormatted(): string {
    if (!this.lastTransfer) return '';
    const date = new Date(this.lastTransfer.transferred_at);

    return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
  }

  public async openModal(): Promise<void> {
    const result = await this.facade.ensureCashSessionOpen();

    if (!result.ok) {
      this.toastService.presentError(result.error);
      return;
    }

    if (this.pinAuthService.requiresPin(AuthActionType.NORMAL)) {
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

    try {
      const order = await this.facade.createOrderForSelectedTable(this.diners);
      this.modalOpen = false;
      void this.router.navigate(['/app/pedidos'], {
        queryParams: { orderId: order.id, tableId: selectedTable.id },
      });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudo abrir la mesa.';
      this.toastService.presentError(message);
    } finally {
      this.openingOrder = false;
    }
  }

  public openCloseAccountModal(): void {
    if (!this.facade.selectedTable()?.order_id) {
      return;
    }

    if (this.pinAuthService.requiresPin(AuthActionType.NORMAL)) {
      this.showPinAuthModalForCloseAccount = true;
    } else {
      this.closeAccountModalOpen = true;
    }
  }

  public onPinAuthenticatedForCloseAccount(result: PinAuthResult): void {
    this.applyPinAuth(result);
    this.showPinAuthModalForCloseAccount = false;
    this.closeAccountModalOpen = true;
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

    try {
      await this.facade.closeAccountForSelectedTable();
      this.closeAccountModalOpen = false;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudo cerrar la cuenta.';
      this.toastService.presentError(message);
    } finally {
      this.closingAccount = false;
    }
  }

  public goToCobrar(): void {
    if (!this.facade.selectedTable()?.order_id) {
      return;
    }

    if (this.pinAuthService.requiresPin(AuthActionType.NORMAL)) {
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
    const tableToSelect = this.tableMenuTable;
    this.closeTableMenu();
    this.enterMergeMode(tableToSelect);
  }

  public onUnmergeTable(): void {
    const groupId = this.tableMenuTable?.merged_table_group_id;
    this.closeTableMenu();

    if (groupId) {
      void this.unmergeTable(groupId);
    }
  }

  public async onTransferAccount(): Promise<void> {
    const menuTable = this.tableMenuTable;
    this.closeTableMenu();

    if (!menuTable?.order_id) {
      return;
    }

    this.transferSourceTable = menuTable;
    this.transferError = null;

    try {
      const paidCount = await this.facade.getPaidDinersCountFromChargeSession(menuTable.order_id);
      this.transferHasPartialPayments = paidCount > 0;
    } catch {
      this.transferHasPartialPayments = false;
    }

    this.transferModalOpen = true;
  }

  public closeTransferModal(): void {
    if (this.transferLoading) {
      return;
    }
    this.transferModalOpen = false;
    this.transferSourceTable = null;
    this.transferHasPartialPayments = false;
    this.transferError = null;
  }

  public async onConfirmTransfer(toTableId: string): Promise<void> {
    if (!this.transferSourceTable?.order_id || this.transferLoading) {
      return;
    }

    this.transferLoading = true;
    this.transferError = null;

    const sourceName = this.transferSourceTable.name;
    const destinationName = this.facade.tables().find((t) => t.id === toTableId)?.name ?? 'la nueva mesa';

    try {
      const sourceOrderId = this.transferSourceTable.order_id;
      await this.facade.transferOrderToTable(sourceOrderId, toTableId);
      this.transferModalOpen = false;
      this.transferSourceTable = null;
      this.transferHasPartialPayments = false;
      await this.loadLastTransferForOrder(sourceOrderId);
      this.toastService.presentSuccess(`Cuenta traspasada de ${sourceName} a ${destinationName}.`);
    } catch (err) {
      this.transferError = err instanceof Error ? err.message : 'No se pudo traspasar la cuenta.';
    } finally {
      this.transferLoading = false;
    }
  }

  public get occupiedTableIds(): string[] {
    return this.facade.tables().filter((t) => t.occupied).map((t) => t.id);
  }

  public enterMergeMode(tableToSelect?: TableWithStatus | null): void {
    this.isMergeMode = true;

    if (tableToSelect) {
      this.selectedTablesForMerge = [tableToSelect.id];
    } else if (this.tableMenuTable) {
      this.selectedTablesForMerge = [this.tableMenuTable.id];
    } else {
      this.selectedTablesForMerge = [];
    }
  }

  public exitMergeMode(): void {
    this.isMergeMode = false;
    this.selectedTablesForMerge = [];
  }

  public toggleTableForMerge(tableId: string): void {
    if (this.selectedTablesForMerge.includes(tableId)) {
      this.selectedTablesForMerge = this.selectedTablesForMerge.filter(id => id !== tableId);
    } else {
      this.selectedTablesForMerge = [...this.selectedTablesForMerge, tableId];
    }
  }

  public isTableSelectedForMerge(tableId: string): boolean {
    return this.selectedTablesForMerge.includes(tableId);
  }

  public onPointerDown(event: PointerEvent, table: TableWithStatus): void {

    if (event.button !== 0) { return; }

    if (this.isMergeMode) { return; }

    this.dragSourceTable = table;
    this.dragStartX = event.clientX;
    this.dragStartY = event.clientY;

    const mesaEl = (event.target as HTMLElement).closest('.mesa') as HTMLElement;
    this.dragSourceElement = mesaEl;

    mesaEl.setPointerCapture(event.pointerId);

    const onPointerMove = (e: PointerEvent): void => {
      const dx = e.clientX - this.dragStartX;
      const dy = e.clientY - this.dragStartY;

      if (!this.dragPreview && (Math.abs(dx) > this.DRAG_THRESHOLD || Math.abs(dy) > this.DRAG_THRESHOLD)) {
        this.startDragPreview();
      }

      if (this.dragPreview) {
        this.moveDragPreview(e.clientX, e.clientY);

        this.dragPreview.style.display = 'none';
        const elementBelow = document.elementFromPoint(e.clientX, e.clientY);
        this.dragPreview.style.display = '';

        if (elementBelow) {
          const mesaElement = elementBelow.closest('.mesa');
          if (mesaElement) {
            const targetId = (mesaElement as HTMLElement).getAttribute('data-table-id');
            this.dragTargetId = targetId !== table.id ? targetId : null;
          } else {
            this.dragTargetId = null;
          }
        } else {
          this.dragTargetId = null;
        }
      }
    };

    const onPointerUp = async (e: PointerEvent): Promise<void> => {
      if (this.dragSourceElement) {
        this.dragSourceElement.releasePointerCapture(event.pointerId);
      }
      document.removeEventListener('pointermove', onPointerMove);
      document.removeEventListener('pointerup', onPointerUp);

      if (this.dragPreview) {
        this.destroyDragPreview();

        if (this.dragTargetId && this.dragTargetId !== table.id) {
          const targetTable = this.facade.tables().find(t => t.id === this.dragTargetId);
          if (targetTable) {
            await this.attemptMerge(table, targetTable);
          }
        }
      }

      this.dragSourceElement = null;
      this.dragSourceTable = null;
      this.dragTargetId = null;
    };

    document.addEventListener('pointermove', onPointerMove);
    document.addEventListener('pointerup', onPointerUp);
  }

  public onFloorTableDragStarted(data: { table: TableWithStatus; event: PointerEvent }): void {
    const { table, event } = data;

    if (event.button !== 0) return;
    if (this.isMergeMode) return;

    this.dragSourceTable = table;
    this.dragStartX = event.clientX;
    this.dragStartY = event.clientY;

    const svgGroup = (event.target as Element).closest('g.fp-table') as SVGElement | null;
    if (!svgGroup) return;

    this.dragSourceElement = svgGroup as unknown as HTMLElement;
    this.dragSourceTableId = table.id;

    const onPointerMove = (e: PointerEvent): void => {
      const dx = e.clientX - this.dragStartX;
      const dy = e.clientY - this.dragStartY;

      if (!this.dragPreview && (Math.abs(dx) > this.DRAG_THRESHOLD || Math.abs(dy) > this.DRAG_THRESHOLD)) {
        this.startDragPreview();
      }

      if (this.dragPreview) {
        this.moveDragPreview(e.clientX, e.clientY);

        this.dragPreview.style.display = 'none';
        const elementBelow = document.elementFromPoint(e.clientX, e.clientY);
        this.dragPreview.style.display = '';

        if (elementBelow) {
          const targetGroup = (elementBelow as Element).closest('g.fp-table');
          if (targetGroup) {
            const targetId = targetGroup.getAttribute('data-table-id');
            this.dragTargetId = targetId !== table.id ? targetId : null;
          } else {
            this.dragTargetId = null;
          }
        } else {
          this.dragTargetId = null;
        }
      }
    };

    const onPointerUp = async (e: PointerEvent): Promise<void> => {
      this.dragSourceTableId = null;

      document.removeEventListener('pointermove', onPointerMove);
      document.removeEventListener('pointerup', onPointerUp);

      if (this.dragPreview) {
        this.destroyDragPreview();

        if (this.dragTargetId && this.dragTargetId !== table.id) {
          const targetTable = this.facade.tables().find(t => t.id === this.dragTargetId);
          if (targetTable) {
            await this.attemptMerge(table, targetTable);
          }
        }
      }

      this.dragSourceElement = null;
      this.dragSourceTable = null;
      this.dragTargetId = null;
    };

    document.addEventListener('pointermove', onPointerMove);
    document.addEventListener('pointerup', onPointerUp);
  }

  private startDragPreview(): void {
    if (!this.dragSourceElement) { return; }

    const rect = this.dragSourceElement.getBoundingClientRect();
    this.dragOffsetX = this.dragStartX - rect.left;
    this.dragOffsetY = this.dragStartY - rect.top;

    // SVG elements cannot be cloned into HTML body — create floating card instead
    let preview: HTMLElement;

    if (this.dragSourceElement.namespaceURI === 'http://www.w3.org/2000/svg') {
      preview = this.createSvgDragPreviewElement(this.dragSourceTable, rect);
      preview.style.position = 'fixed';
      preview.style.left = `${rect.left}px`;
      preview.style.top = `${rect.top}px`;
      preview.style.width = `${rect.width}px`;
      preview.style.height = `${rect.height}px`;
      preview.style.borderRadius = '18px';
      preview.style.overflow = 'hidden';
      preview.style.boxShadow = '0 12px 32px rgba(0,0,0,0.2), 0 0 0 3px rgba(34,197,94,0.35)';
      preview.style.zIndex = '9999';
      preview.style.pointerEvents = 'none';
      preview.style.margin = '0';
      preview.style.opacity = '0.95';
    } else {
      preview = this.dragSourceElement.cloneNode(true) as HTMLElement;
      preview.classList.add('mesa-drag-preview');
      preview.style.position = 'fixed';
      preview.style.left = `${rect.left}px`;
      preview.style.top = `${rect.top}px`;
      preview.style.width = `${rect.width}px`;
      preview.style.height = `${rect.height}px`;
      preview.style.zIndex = '9999';
      preview.style.pointerEvents = 'none';
      preview.style.margin = '0';
      preview.style.transform = 'scale(1.08)';
      preview.style.opacity = '0.95';
    }

    document.body.appendChild(preview);
    this.dragPreview = preview;

    // HTML elements use inline opacity; SVG uses CSS class (fp-dragging-source)
    if (this.dragSourceElement.namespaceURI !== 'http://www.w3.org/2000/svg') {
      this.dragSourceElement.style.opacity = '0.4';
    }
  }

  private createSvgDragPreviewElement(table: TableWithStatus | null, _rect: DOMRect): HTMLElement {
    const card = document.createElement('div');
    card.style.position = 'relative';

    if (table?.status === OrderStatus.TO_CHARGE) {
      card.style.background = '#1A6FE8';
      card.style.border = '2px solid #1A6FE8';
    } else if (table?.occupied) {
      card.style.background = '#0D0D0D';
      card.style.border = '2px solid #0D0D0D';
    } else {
      card.style.background = '#ffffff';
      card.style.border = '2px solid #E8E8E8';
    }

    const nameEl = document.createElement('span');
    nameEl.textContent = table?.name ?? '';
    nameEl.style.position = 'absolute';
    nameEl.style.top = '50%';
    nameEl.style.left = '50%';
    nameEl.style.transform = 'translate(-50%, -50%)';
    nameEl.style.fontSize = '14px';
    nameEl.style.fontWeight = '600';
    nameEl.style.fontFamily = "'DM Sans', sans-serif";
    nameEl.style.lineHeight = '1.2';
    nameEl.style.whiteSpace = 'nowrap';
    nameEl.style.color = table?.occupied || table?.status === OrderStatus.TO_CHARGE ? '#ffffff' : '#0D0D0D';
    card.appendChild(nameEl);

    if (table?.status === OrderStatus.TO_CHARGE) {
      const tag = document.createElement('span');
      tag.textContent = 'COBRAR';
      tag.style.position = 'absolute';
      tag.style.bottom = '10px';
      tag.style.left = '50%';
      tag.style.transform = 'translateX(-50%)';
      tag.style.fontSize = '9px';
      tag.style.fontWeight = '700';
      tag.style.letterSpacing = '0.08em';
      tag.style.color = '#fff';
      tag.style.padding = '3px 8px';
      tag.style.borderRadius = '999px';
      tag.style.background = 'rgba(255, 255, 255, 0.18)';
      tag.style.whiteSpace = 'nowrap';
      card.appendChild(tag);
    }

    return card;
  }

  private moveDragPreview(x: number, y: number): void {
    if (this.dragPreview) {
      this.dragPreview.style.left = `${x - this.dragOffsetX}px`;
      this.dragPreview.style.top = `${y - this.dragOffsetY}px`;
    }
  }

  private destroyDragPreview(): void {
    if (this.dragPreview) {
      document.body.removeChild(this.dragPreview);
      this.dragPreview = null;
    }

    if (this.dragSourceElement) {
      this.dragSourceElement.style.opacity = '';
    }
  }

  private hasTableToCharge(table: TableWithStatus): boolean {
    if (table.status === OrderStatus.TO_CHARGE) {
      return true;
    }
    if (table.merged_table_group_id) {
      const group = this.facade.tablesByMergedGroup().get(table.merged_table_group_id);
      if (group) {
        return group.some(t => t.status === OrderStatus.TO_CHARGE);
      }
    }
    return false;
  }

  private async attemptMerge(sourceTable: TableWithStatus, targetTable: TableWithStatus): Promise<void> {
    if (sourceTable.zone_id !== targetTable.zone_id) {
      this.toastService.presentError('Solo se pueden juntar mesas de la misma zona');
      return;
    }

    if (this.hasTableToCharge(sourceTable) || this.hasTableToCharge(targetTable)) {
      this.toastService.presentError('No se pueden juntar mesas con órdenes marcadas para cobrar');
      return;
    }

    if (
      sourceTable.merged_table_group_id &&
      targetTable.merged_table_group_id &&
      sourceTable.merged_table_group_id === targetTable.merged_table_group_id
    ) {
      return;
    }

    try {
      await this.facade.mergeTables([sourceTable.id, targetTable.id]);
      this.addAnchor(targetTable.id);
      this.toastService.presentSuccess('Mesas fusionadas correctamente');
      await this.facade.loadData();
      this.pruneAnchors();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudieron fusionar las mesas.';
      this.toastService.presentError(message);
    }
  }

  public async confirmMergeTables(): Promise<void> {
    if (this.mergingTables || this.selectedTablesForMerge.length < 2) {
      return;
    }

    for (const tableId of this.selectedTablesForMerge) {
      const table = this.facade.tables().find(t => t.id === tableId);
      if (table && this.hasTableToCharge(table)) {
        this.toastService.presentError('No se pueden juntar mesas con órdenes marcadas para cobrar');
        return;
      }
    }

    this.mergingTables = true;

    try {
      await this.facade.mergeTables(this.selectedTablesForMerge);
      this.addAnchor(this.selectedTablesForMerge[0]);
      this.toastService.presentSuccess('Mesas fusionadas correctamente');
      await this.facade.loadData();
      this.pruneAnchors();
      this.exitMergeMode();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudieron fusionar las mesas.';
      this.toastService.presentError(message);
    } finally {
      this.mergingTables = false;
    }
  }

  public async unmergeTable(groupId: string): Promise<void> {
    try {
      await this.facade.unmergeTables(groupId);
      this.toastService.presentSuccess('Mesas separadas correctamente');
      await this.facade.loadData();
      this.pruneAnchors();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'No se pudieron separar las mesas.';
      this.toastService.presentError(message);
    }
  }

  public openLineDetail(line: TpvOrderLine): void {
    this.selectedLine = line;
    this.detailModalOpen = true;
  }

  public closeLineDetail(): void {
    this.detailModalOpen = false;
    this.selectedLine = null;
  }

  public formatModifiers(modifiers: { name: string }[]): string {
    return modifiers.map((m) => m.name).join(', ');
  }

  public getLineTotal(line: TpvOrderLine): number {
    const modTotal = (line.modifiers ?? []).reduce((acc, m) => acc + m.price, 0);
    return (line.price + modTotal) * line.quantity;
  }

  public isMenuLine(line: TpvOrderLine): boolean {
    return !!line.menu_id;
  }

  public lineDisplayName(line: TpvOrderLine): string {
    if (this.isMenuLine(line)) {
      return line.menu_name ?? 'Menú';
    }
    return line.product_name ?? 'Producto';
  }

  public menuLineSelectionsLabel(line: TpvOrderLine): string {
    if (!line.menu_selections || line.menu_selections.length === 0) return '';
    return line.menu_selections.map((s) => s.product_name).join(', ');
  }

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

  public getMergedTableName(mergedTables: TableWithStatus[]): string {
    return mergedTables.map(t => t.name).join(' + ');
  }

  public getMergedTableTotal(mergedTables: TableWithStatus[]): number {
    return mergedTables.reduce((sum, t) => sum + (t.remaining_total ?? 0), 0);
  }

  public getMergedTableStatus(mergedTables: TableWithStatus[]): OrderStatus | undefined {
    // Priorizar TO_CHARGE sobre OPEN
    const toChargeTable = mergedTables.find(t => t.status === OrderStatus.TO_CHARGE);
    if (toChargeTable) {
      return toChargeTable.status;
    }

    const openTable = mergedTables.find(t => t.status === OrderStatus.OPEN);
    if (openTable) {
      return openTable.status;
    }

    return undefined;
  }

  public isAnyMergedTableOccupied(mergedTables: TableWithStatus[]): boolean {
    return mergedTables.some(t => t.occupied);
  }

  // ----- Private helpers -----
  private openOpenTableModal(): void {
    this.modalOpen = true;
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
